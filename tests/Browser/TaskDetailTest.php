<?php

declare(strict_types=1);

use App\Enums\TaskStatus;
use App\Enums\TeamRole;
use App\Models\Membership;
use App\Models\Project;
use App\Models\Task;

test('a task opens from the project task list with its description rendered as markdown', function () {
    $membership = Membership::factory()->role(TeamRole::Viewer)->create();
    $project = Project::factory()->for($membership->team)->create(['identifier' => 'WEB']);
    Task::factory()->for($project)->create([
        'title' => 'Fix the login form',
        'description' => "The **submit** button does nothing.\n\n<script>window.injected = true</script><b>raw html</b>",
        'author_id' => null,
    ]);
    $this->actingAs($membership->user);

    $page = visit(route('projects.show', ['current_team' => $membership->team, 'project' => $project], absolute: false));

    $page->click('@task-link')
        ->assertSee('WEB-1')
        ->assertSeeIn('@task-author', 'Deleted user')
        ->assertSourceHas('<strong>submit</strong>')
        ->assertSourceMissing('<b>raw html</b>')
        ->assertScript('window.injected === undefined', true)
        ->assertAttribute('@task-title', 'disabled', '')
        ->assertNoJavaScriptErrors();
});

test('members edit the task fields in place', function () {
    $membership = Membership::factory()->role(TeamRole::Member)->create();
    $task = Task::factory()->for(Project::factory()->for($membership->team))->create([
        'title' => 'Fix the login form',
        'description' => null,
        'status' => TaskStatus::Backlog,
    ]);
    $this->actingAs($membership->user);

    $page = visit(route('tasks.show', ['current_team' => $membership->team, 'task' => $task], absolute: false));

    $page->fill('@task-title', 'Fix the signup form')
        ->keys('@task-title', 'Enter')
        ->click('@task-status')
        ->click('In Progress')
        ->click('@task-description')
        ->fill('@task-description-input', 'Steps to **reproduce**')
        ->click('@task-description-save')
        ->assertSourceHas('<strong>reproduce</strong>')
        ->assertNoJavaScriptErrors();

    expect($task->fresh())
        ->title->toBe('Fix the signup form')
        ->status->toBe(TaskStatus::InProgress)
        ->description->toBe('Steps to **reproduce**');
});

test('an admin deletes the task after confirming', function () {
    $membership = Membership::factory()->role(TeamRole::Admin)->create();
    $project = Project::factory()->for($membership->team)->create(['identifier' => 'WEB']);
    $task = Task::factory()->for($project)->create();
    $this->actingAs($membership->user);

    $page = visit(route('tasks.show', ['current_team' => $membership->team, 'task' => $task], absolute: false));

    $page->click('@task-actions')
        ->click('@task-delete')
        ->click('@task-delete-confirm')
        ->assertSee('WEB-1 deleted.')
        ->assertPathIs(route('projects.show', ['current_team' => $membership->team, 'project' => $project], absolute: false))
        ->assertNoJavaScriptErrors();

    expect($project->tasks()->exists())->toBeFalse();
});
