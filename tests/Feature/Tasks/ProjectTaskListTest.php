<?php

declare(strict_types=1);

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Enums\TeamRole;
use App\Models\Membership;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Collection;
use Inertia\Testing\AssertableInertia as Assert;

test('members of any role see the project tasks, newest first', function () {
    $membership = Membership::factory()->role(TeamRole::Viewer)->create();
    $assignee = User::factory()->create(['name' => 'Jana Nováková']);
    Membership::factory()->for($membership->team)->for($assignee)->create();
    $project = Project::factory()->for($membership->team)->create(['identifier' => 'WEB']);
    Task::factory()->for($project)->create(['title' => 'Older']);
    Task::factory()->for($project)->for($assignee, 'assignee')->create([
        'title' => 'Fix the login form',
        'status' => TaskStatus::InProgress,
        'priority' => TaskPriority::High,
        'due_date' => '2026-11-02',
    ]);
    Task::factory()->create();

    $response = $this
        ->actingAs($membership->user)
        ->get(route('projects.show', ['current_team' => $project->team, 'project' => $project]));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('projects/show')
        ->where('project.id', $project->id)
        ->where('tasks.total', 2)
        ->where('tasks.data.0.reference', 'WEB-2')
        ->where('tasks.data.0.title', 'Fix the login form')
        ->where('tasks.data.0.status', 'in_progress')
        ->where('tasks.data.0.priority', 'high')
        ->where('tasks.data.0.assignee', ['id' => $assignee->id, 'name' => 'Jana Nováková'])
        ->where('tasks.data.0.dueDate', '2026-11-02')
        ->where('tasks.data.0.project.identifier', 'WEB')
        ->where('tasks.data.1.reference', 'WEB-1')
        ->where('tasks.data.1.assignee', null)
        ->where('members', fn (Collection $members) => $members->pluck('name')->contains('Jana Nováková'))
        ->where('filters', ['search' => '', 'status' => [], 'priority' => [], 'assignee' => [], 'project' => [], 'sort' => '-number']),
    );
});

test('tasks are filtered by status, priority and assignee, including unassigned', function () {
    $membership = Membership::factory()->role(TeamRole::Member)->create();
    $project = Project::factory()->for($membership->team)->create();
    $me = $membership->user;
    $mine = Task::factory()->for($project)->for($me, 'assignee')->create(['status' => TaskStatus::Todo, 'priority' => TaskPriority::High]);
    $unassigned = Task::factory()->for($project)->create(['status' => TaskStatus::InProgress, 'priority' => TaskPriority::High]);
    Task::factory()->for($project)->create(['status' => TaskStatus::Done, 'priority' => TaskPriority::High]);
    Task::factory()->for($project)->create(['status' => TaskStatus::Todo, 'priority' => TaskPriority::Low]);
    Task::factory()->for($project)->for(Membership::factory()->for($membership->team)->create()->user, 'assignee')
        ->create(['status' => TaskStatus::Todo, 'priority' => TaskPriority::High]);

    $response = $this
        ->actingAs($me)
        ->get(route('projects.show', ['current_team' => $project->team, 'project' => $project, ...[
            'status' => ['todo', 'in_progress', 'nonsense'],
            'priority' => ['high'],
            'assignee' => [$me->id, 'unassigned'],
        ]]));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('tasks.total', 2)
        ->where('tasks.data', fn (Collection $tasks) => $tasks->pluck('id')->sort()->values()->all() === collect([$mine->id, $unassigned->id])->sort()->values()->all())
        ->where('filters.status', ['todo', 'in_progress'])
        ->where('filters.assignee', [$me->id, 'unassigned']),
    );
});

test('the search matches the title or the task reference', function (string $search, array $expected) {
    $membership = Membership::factory()->role(TeamRole::Member)->create();
    $project = Project::factory()->for($membership->team)->create(['identifier' => 'WEB']);
    Task::factory()->for($project)->create(['title' => 'Fix the login form']);
    Task::factory()->for($project)->create(['title' => 'Write release notes']);
    Task::factory()->for($project)->create(['title' => 'Login with passkeys']);
    Task::factory()->for($project)->create(['title' => 'Reach 100% coverage']);
    Task::factory()->for($project)->create(['title' => 'Rename the user_id column']);

    $response = $this
        ->actingAs($membership->user)
        ->get(route('projects.show', ['current_team' => $project->team, 'project' => $project, ...['search' => $search]]));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('tasks.data', fn (Collection $tasks) => $tasks->pluck('reference')->all() === $expected)
        ->where('filters.search', $search),
    );
})->with([
    'title, any case' => ['LOGIN', ['WEB-3', 'WEB-1']],
    'reference' => ['WEB-2', ['WEB-2']],
    'reference in lowercase' => ['web-1', ['WEB-1']],
    'no match' => ['deploy', []],
    'percent sign, literally' => ['%', ['WEB-4']],
    'underscore, literally' => ['_', ['WEB-5']],
]);

test('tasks are sorted by the requested column', function (string $sort, array $expected) {
    $membership = Membership::factory()->role(TeamRole::Member)->create();
    $project = Project::factory()->for($membership->team)->create(['identifier' => 'WEB']);
    Task::factory()->for($project)->create(['title' => 'Bravo', 'priority' => TaskPriority::Low, 'status' => TaskStatus::Done, 'due_date' => null]);
    Task::factory()->for($project)->create(['title' => 'Alpha', 'priority' => TaskPriority::Urgent, 'status' => TaskStatus::Backlog, 'due_date' => '2026-12-01']);
    Task::factory()->for($project)->create(['title' => 'Charlie', 'priority' => TaskPriority::None, 'status' => TaskStatus::InProgress, 'due_date' => '2026-11-01']);

    $response = $this
        ->actingAs($membership->user)
        ->get(route('projects.show', ['current_team' => $project->team, 'project' => $project, ...['sort' => $sort]]));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('tasks.data', fn (Collection $tasks) => $tasks->pluck('reference')->all() === $expected),
    );
})->with([
    'number ascending' => ['number', ['WEB-1', 'WEB-2', 'WEB-3']],
    'title' => ['title', ['WEB-2', 'WEB-1', 'WEB-3']],
    'most urgent first' => ['-priority', ['WEB-2', 'WEB-1', 'WEB-3']],
    'least urgent first' => ['priority', ['WEB-3', 'WEB-1', 'WEB-2']],
    'status in workflow order' => ['status', ['WEB-2', 'WEB-3', 'WEB-1']],
    'due date, undated last' => ['due_date', ['WEB-3', 'WEB-2', 'WEB-1']],
    'due date descending, undated last' => ['-due_date', ['WEB-2', 'WEB-3', 'WEB-1']],
    'unknown column falls back to newest first' => ['-secret', ['WEB-3', 'WEB-2', 'WEB-1']],
]);

test('tasks are paginated 25 per page', function () {
    $membership = Membership::factory()->role(TeamRole::Member)->create();
    $project = Project::factory()->for($membership->team)->create(['identifier' => 'WEB']);
    Task::factory()->for($project)->count(26)->create();

    $response = $this
        ->actingAs($membership->user)
        ->get(route('projects.show', ['current_team' => $project->team, 'project' => $project, ...['page' => 2, 'status' => ['backlog']]]));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('tasks.total', 26)
        ->where('tasks.from', 26)
        ->where('tasks.to', 26)
        ->where('tasks.currentPage', 2)
        ->where('tasks.lastPage', 2)
        ->where('tasks.nextPageUrl', null)
        ->where('tasks.prevPageUrl', fn (string $url) => str_contains($url, 'page=1') && str_contains($url, 'status'))
        ->where('tasks.data.0.reference', 'WEB-1'),
    );
});

test('facet counts apply the other filters but not their own', function () {
    $membership = Membership::factory()->role(TeamRole::Member)->create();
    $project = Project::factory()->for($membership->team)->create();
    $me = $membership->user;
    Task::factory()->for($project)->for($me, 'assignee')->create(['status' => TaskStatus::Todo, 'priority' => TaskPriority::High, 'title' => 'Login']);
    Task::factory()->for($project)->create(['status' => TaskStatus::Done, 'priority' => TaskPriority::High, 'title' => 'Login']);
    Task::factory()->for($project)->create(['status' => TaskStatus::Todo, 'priority' => TaskPriority::Low, 'title' => 'Login']);
    Task::factory()->for($project)->create(['status' => TaskStatus::Todo, 'priority' => TaskPriority::High, 'title' => 'Signup']);

    $response = $this
        ->actingAs($me)
        ->get(route('projects.show', ['current_team' => $project->team, 'project' => $project, ...['search' => 'login', 'status' => ['todo'], 'priority' => ['high']]]));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('facets.status', ['todo' => 1, 'done' => 1])
        ->where('facets.priority', ['high' => 1, 'low' => 1])
        ->where('facets.assignee', [$me->id => 1]),
    );
});
