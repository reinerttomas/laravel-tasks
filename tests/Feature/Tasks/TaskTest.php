<?php

declare(strict_types=1);

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Enums\TeamRole;
use App\Models\Membership;
use App\Models\Project;
use App\Models\Task;

describe('store', function () {
    test('members create a task with defaults for status and priority', function () {
        $membership = Membership::factory()->role(TeamRole::Member)->create();
        $project = Project::factory()->for($membership->team)->create(['identifier' => 'WEB']);

        $response = $this
            ->actingAs($membership->user)
            ->from(route('projects.show', ['current_team' => $membership->team, 'project' => $project]))
            ->post(route('tasks.store', ['current_team' => $membership->team, 'project' => $project]), [
                'title' => 'Fix the login form',
            ]);

        $response->assertRedirect(route('projects.show', ['current_team' => $membership->team, 'project' => $project]));
        $response->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'WEB-1 created.']);

        expect($project->tasks()->sole())
            ->title->toBe('Fix the login form')
            ->number->toBe(1)
            ->reference->toBe('WEB-1')
            ->description->toBeNull()
            ->status->toBe(TaskStatus::Backlog)
            ->priority->toBe(TaskPriority::None)
            ->assignee_id->toBeNull()
            ->author_id->toBe($membership->user->id)
            ->due_date->toBeNull();
    });

    test('a task is created with every field', function () {
        $membership = Membership::factory()->role(TeamRole::Member)->create();
        $assignee = Membership::factory()->for($membership->team)->role(TeamRole::Viewer)->create()->user;
        $project = Project::factory()->for($membership->team)->create();

        $this
            ->actingAs($membership->user)
            ->post(route('tasks.store', ['current_team' => $membership->team, 'project' => $project]), [
                'title' => 'Fix the login form',
                'description' => 'The **submit** button does nothing.',
                'status' => 'in_progress',
                'priority' => 'urgent',
                'assignee_id' => $assignee->id,
                'due_date' => '2026-11-02',
            ])
            ->assertValid();

        expect($project->tasks()->sole())
            ->description->toBe('The **submit** button does nothing.')
            ->status->toBe(TaskStatus::InProgress)
            ->priority->toBe(TaskPriority::Urgent)
            ->assignee_id->toBe($assignee->id);
        expect($project->tasks()->sole()->due_date?->toDateString())->toBe('2026-11-02');
    });

    test('task numbers are sequential per project and never reused after a deletion', function () {
        $membership = Membership::factory()->role(TeamRole::Owner)->create();
        $project = Project::factory()->for($membership->team)->create();
        $otherProject = Project::factory()->for($membership->team)->create();
        $this->actingAs($membership->user);

        $store = fn (Project $project) => $this->post(route('tasks.store', ['current_team' => $membership->team, 'project' => $project]), ['title' => 'Task']);

        $store($project);
        $store($project);
        $this->delete(route('tasks.destroy', ['current_team' => $membership->team, 'task' => $project->tasks()->where('number', 2)->sole()]));
        $store($project);
        $store($otherProject);

        expect($project->tasks()->orderBy('number')->pluck('number')->all())->toBe([1, 3])
            ->and($otherProject->tasks()->sole()->number)->toBe(1);
    });

    test('the title is required and the other fields must be valid', function () {
        $membership = Membership::factory()->role(TeamRole::Owner)->create();
        $project = Project::factory()->for($membership->team)->create();

        $response = $this
            ->actingAs($membership->user)
            ->post(route('tasks.store', ['current_team' => $membership->team, 'project' => $project]), [
                'status' => 'blocked',
                'priority' => 'critical',
                'due_date' => 'tomorrow-ish',
            ]);

        $response->assertInvalid([
            'title' => 'The title field is required.',
            'status' => 'The selected status is invalid.',
            'priority' => 'The selected priority is invalid.',
            'due_date' => 'The due date field must be a valid date.',
        ]);
        expect($project->tasks()->exists())->toBeFalse();
    });

    test('the assignee must be a member of the team', function () {
        $membership = Membership::factory()->role(TeamRole::Owner)->create();
        $project = Project::factory()->for($membership->team)->create();
        $outsider = Membership::factory()->create()->user;

        $response = $this
            ->actingAs($membership->user)
            ->post(route('tasks.store', ['current_team' => $membership->team, 'project' => $project]), [
                'title' => 'Fix the login form',
                'assignee_id' => $outsider->id,
            ]);

        $response->assertInvalid(['assignee_id' => 'The assignee must be a member of this team.']);
        expect($project->tasks()->exists())->toBeFalse();
    });

    test('viewers cannot create a task', function () {
        $membership = Membership::factory()->role(TeamRole::Viewer)->create();
        $project = Project::factory()->for($membership->team)->create();

        $response = $this
            ->actingAs($membership->user)
            ->post(route('tasks.store', ['current_team' => $membership->team, 'project' => $project]), ['title' => 'Task']);

        $response->assertForbidden();
        expect($project->tasks()->exists())->toBeFalse();
    });

    test('an archived project rejects new tasks', function () {
        $membership = Membership::factory()->role(TeamRole::Owner)->create();
        $project = Project::factory()->for($membership->team)->archived()->create();

        $response = $this
            ->actingAs($membership->user)
            ->post(route('tasks.store', ['current_team' => $membership->team, 'project' => $project]), ['title' => 'Task']);

        $response->assertForbidden();
        expect($project->tasks()->exists())->toBeFalse();
    });

    test('a project of another team returns 404', function () {
        $membership = Membership::factory()->role(TeamRole::Owner)->create();
        $project = Project::factory()->create();

        $response = $this
            ->actingAs($membership->user)
            ->post(route('tasks.store', ['current_team' => $membership->team, 'project' => $project]), ['title' => 'Task']);

        $response->assertNotFound();
        expect($project->tasks()->exists())->toBeFalse();
    });
});

describe('update', function () {
    test('members update only the fields they send', function () {
        $membership = Membership::factory()->role(TeamRole::Member)->create();
        $assignee = Membership::factory()->for($membership->team)->create()->user;
        $task = Task::factory()
            ->for(Project::factory()->for($membership->team))
            ->create(['title' => 'Fix the login form', 'description' => 'Keep me.', 'due_date' => '2026-11-02']);

        $response = $this
            ->actingAs($membership->user)
            ->from(route('dashboard', ['current_team' => $membership->team]))
            ->patch(route('tasks.update', ['current_team' => $membership->team, 'task' => $task]), [
                'status' => 'done',
                'priority' => 'low',
                'assignee_id' => $assignee->id,
                'due_date' => null,
            ]);

        $response->assertRedirect(route('dashboard', ['current_team' => $membership->team]));
        expect($task->fresh())
            ->title->toBe('Fix the login form')
            ->description->toBe('Keep me.')
            ->status->toBe(TaskStatus::Done)
            ->priority->toBe(TaskPriority::Low)
            ->assignee_id->toBe($assignee->id)
            ->due_date->toBeNull();
    });

    test('the title and description can be changed and cleared', function () {
        $membership = Membership::factory()->role(TeamRole::Member)->create();
        $task = Task::factory()->for(Project::factory()->for($membership->team))->create(['assignee_id' => $membership->user->id]);

        $this
            ->actingAs($membership->user)
            ->patch(route('tasks.update', ['current_team' => $membership->team, 'task' => $task]), [
                'title' => 'Fix the signup form',
                'description' => '',
                'assignee_id' => null,
            ])
            ->assertValid();

        expect($task->fresh())
            ->title->toBe('Fix the signup form')
            ->description->toBeNull()
            ->assignee_id->toBeNull();
    });

    test('the title cannot be emptied', function () {
        $membership = Membership::factory()->role(TeamRole::Member)->create();
        $task = Task::factory()->for(Project::factory()->for($membership->team))->create(['title' => 'Fix the login form']);

        $response = $this
            ->actingAs($membership->user)
            ->patch(route('tasks.update', ['current_team' => $membership->team, 'task' => $task]), ['title' => '']);

        $response->assertInvalid(['title' => 'The title field is required.']);
        expect($task->fresh()->title)->toBe('Fix the login form');
    });

    test('the assignee must be a member of the team', function () {
        $membership = Membership::factory()->role(TeamRole::Member)->create();
        $task = Task::factory()->for(Project::factory()->for($membership->team))->create();
        $outsider = Membership::factory()->create()->user;

        $response = $this
            ->actingAs($membership->user)
            ->patch(route('tasks.update', ['current_team' => $membership->team, 'task' => $task]), ['assignee_id' => $outsider->id]);

        $response->assertInvalid(['assignee_id' => 'The assignee must be a member of this team.']);
        expect($task->fresh()->assignee_id)->toBeNull();
    });

    test('viewers cannot update a task', function () {
        $membership = Membership::factory()->role(TeamRole::Viewer)->create();
        $task = Task::factory()->for(Project::factory()->for($membership->team))->create(['title' => 'Fix the login form']);

        $response = $this
            ->actingAs($membership->user)
            ->patch(route('tasks.update', ['current_team' => $membership->team, 'task' => $task]), ['title' => 'Renamed']);

        $response->assertForbidden();
        expect($task->fresh()->title)->toBe('Fix the login form');
    });

    test('a task in an archived project cannot be updated', function () {
        $membership = Membership::factory()->role(TeamRole::Owner)->create();
        $task = Task::factory()->for(Project::factory()->for($membership->team)->archived())->create(['title' => 'Fix the login form']);

        $response = $this
            ->actingAs($membership->user)
            ->patch(route('tasks.update', ['current_team' => $membership->team, 'task' => $task]), ['title' => 'Renamed']);

        $response->assertForbidden();
        expect($task->fresh()->title)->toBe('Fix the login form');
    });

    test('a task of another team returns 404', function () {
        $membership = Membership::factory()->role(TeamRole::Owner)->create();
        $task = Task::factory()->create(['title' => 'Fix the login form']);

        $response = $this
            ->actingAs($membership->user)
            ->patch(route('tasks.update', ['current_team' => $membership->team, 'task' => $task]), ['title' => 'Renamed']);

        $response->assertNotFound();
        expect($task->fresh()->title)->toBe('Fix the login form');
    });
});

describe('destroy', function () {
    test('members delete their own task and return to the project', function () {
        $membership = Membership::factory()->role(TeamRole::Member)->create();
        $project = Project::factory()->for($membership->team)->create(['identifier' => 'WEB']);
        $task = Task::factory()->for($project)->for($membership->user, 'author')->create();

        $response = $this
            ->actingAs($membership->user)
            ->delete(route('tasks.destroy', ['current_team' => $membership->team, 'task' => $task]));

        $response->assertRedirect(route('projects.show', ['current_team' => $membership->team, 'project' => $project]));
        $response->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'WEB-1 deleted.']);
        expect($task->fresh())->toBeNull();
    });

    test('members cannot delete a task someone else wrote', function () {
        $membership = Membership::factory()->role(TeamRole::Member)->create();
        $task = Task::factory()->for(Project::factory()->for($membership->team))->create();

        $response = $this
            ->actingAs($membership->user)
            ->delete(route('tasks.destroy', ['current_team' => $membership->team, 'task' => $task]));

        $response->assertForbidden();
        expect($task->fresh())->not->toBeNull();
    });

    test('a task of another team returns 404', function () {
        $membership = Membership::factory()->role(TeamRole::Owner)->create();
        $task = Task::factory()->create();

        $response = $this
            ->actingAs($membership->user)
            ->delete(route('tasks.destroy', ['current_team' => $membership->team, 'task' => $task]));

        $response->assertNotFound();
        expect($task->fresh())->not->toBeNull();
    });
});
