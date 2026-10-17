<?php

declare(strict_types=1);

use App\Enums\TaskStatus;
use App\Enums\TeamRole;
use App\Models\Membership;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Support\Collection;
use Inertia\Testing\AssertableInertia as Assert;

test('my tasks lists only my open tasks in the active projects of the current team', function () {
    $membership = Membership::factory()->role(TeamRole::Viewer)->create();
    $me = $membership->user;
    $web = Project::factory()->for($membership->team)->create(['identifier' => 'WEB']);
    $api = Project::factory()->for($membership->team)->create(['identifier' => 'API']);
    $archived = Project::factory()->for($membership->team)->archived()->create();
    $otherTeamMembership = Membership::factory()->for($me)->create();
    $otherTeamProject = Project::factory()->for($otherTeamMembership->team)->create();

    Task::factory()->for($web)->for($me, 'assignee')->create(['title' => 'Fix the login form', 'status' => TaskStatus::InProgress]);
    Task::factory()->for($api)->for($me, 'assignee')->create(['title' => 'Document the API', 'status' => TaskStatus::Backlog]);
    Task::factory()->for($web)->for($me, 'assignee')->create(['status' => TaskStatus::Done]);
    Task::factory()->for($web)->create(['status' => TaskStatus::Todo]);
    Task::factory()->for($archived)->for($me, 'assignee')->create(['status' => TaskStatus::Todo]);
    Task::factory()->for($otherTeamProject)->for($me, 'assignee')->create(['status' => TaskStatus::Todo]);

    $response = $this
        ->actingAs($me)
        ->get(route('my-tasks', ['current_team' => $membership->team]));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('my-tasks')
        ->where('tasks.total', 2)
        ->where('tasks.data', fn (Collection $tasks) => $tasks->pluck('title')->sort()->values()->all() === ['Document the API', 'Fix the login form'])
        ->where('filters.status', ['backlog', 'todo', 'in_progress'])
        ->where('facets.status', ['backlog' => 1, 'in_progress' => 1, 'done' => 1])
        ->where('facets.project', [$web->id => 1, $api->id => 1]),
    );
});

test('an explicitly empty status filter shows my tasks of every status', function () {
    $membership = Membership::factory()->role(TeamRole::Member)->create();
    $project = Project::factory()->for($membership->team)->create();
    Task::factory()->for($project)->for($membership->user, 'assignee')->create(['status' => TaskStatus::Done]);
    Task::factory()->for($project)->for($membership->user, 'assignee')->create(['status' => TaskStatus::Todo]);

    $response = $this
        ->actingAs($membership->user)
        ->get(route('my-tasks', ['current_team' => $membership->team]).'?status[]=');

    $response->assertInertia(fn (Assert $page) => $page
        ->where('tasks.total', 2)
        ->where('filters.status', []),
    );
});

test('my tasks are filtered by project', function () {
    $membership = Membership::factory()->role(TeamRole::Member)->create();
    $web = Project::factory()->for($membership->team)->create();
    $api = Project::factory()->for($membership->team)->create();
    $mine = Task::factory()->for($web)->for($membership->user, 'assignee')->create(['status' => TaskStatus::Todo]);
    Task::factory()->for($api)->for($membership->user, 'assignee')->create(['status' => TaskStatus::Todo]);

    $response = $this
        ->actingAs($membership->user)
        ->get(route('my-tasks', ['current_team' => $membership->team, 'project' => [$web->id]]));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('tasks.total', 1)
        ->where('tasks.data.0.id', $mine->id)
        ->where('filters.project', [$web->id]),
    );
});

test('guests are redirected from my tasks to the login page', function () {
    $membership = Membership::factory()->create();

    $this->get(route('my-tasks', ['current_team' => $membership->team]))
        ->assertRedirect(route('login'));
});
