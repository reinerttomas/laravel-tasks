<?php

declare(strict_types=1);

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Enums\TeamRole;
use App\Models\Comment;
use App\Models\Membership;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Collection;
use Inertia\Testing\AssertableInertia as Assert;

test('members of every role see the task', function (TeamRole $role, bool $canUpdate, bool $canDelete) {
    $membership = Membership::factory()->role($role)->create();
    $author = Membership::factory()->for($membership->team)->create()->user;
    $assignee = User::factory()->create(['name' => 'Jana Nováková']);
    Membership::factory()->for($membership->team)->for($assignee)->create();
    $project = Project::factory()->for($membership->team)->create(['name' => 'Website', 'identifier' => 'WEB']);
    $task = Task::factory()->for($project)->for($author, 'author')->for($assignee, 'assignee')->create([
        'title' => 'Fix the login form',
        'description' => 'The **submit** button does nothing.',
        'status' => TaskStatus::InProgress,
        'priority' => TaskPriority::High,
        'due_date' => '2026-11-02',
    ]);

    $response = $this
        ->actingAs($membership->user)
        ->get(route('tasks.show', ['current_team' => $membership->team, 'task' => $task]));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('tasks/show')
        ->where('task.id', $task->id)
        ->where('task.reference', 'WEB-1')
        ->where('task.title', 'Fix the login form')
        ->where('task.description', 'The **submit** button does nothing.')
        ->where('task.status', 'in_progress')
        ->where('task.priority', 'high')
        ->where('task.assignee', ['id' => $assignee->id, 'name' => 'Jana Nováková'])
        ->where('task.author', ['id' => $author->id, 'name' => $author->name])
        ->where('task.dueDate', '2026-11-02')
        ->where('task.project.name', 'Website')
        ->where('members', fn (Collection $members) => $members->pluck('name')->contains('Jana Nováková'))
        ->where('can', ['update' => $canUpdate, 'delete' => $canDelete, 'createComment' => $canUpdate]),
    );
})->with([
    'owner' => [TeamRole::Owner, true, true],
    'admin' => [TeamRole::Admin, true, true],
    'member, not the author' => [TeamRole::Member, true, false],
    'viewer' => [TeamRole::Viewer, false, false],
]);

test('the comment thread lists comments oldest first with what the viewer may do to each', function () {
    $membership = Membership::factory()->role(TeamRole::Member)->create();
    $colleague = User::factory()->create(['name' => 'Jana Nováková']);
    $task = Task::factory()->for(Project::factory()->for($membership->team))->create();
    $mine = Comment::factory()->for($task)->for($membership->user, 'author')->create([
        'body' => 'Fixed in **#12**.',
        'created_at' => '2026-10-02 09:00:00',
    ]);
    $theirs = Comment::factory()->for($task)->for($colleague, 'author')->create([
        'body' => 'Reproduced on Safari.',
        'created_at' => '2026-10-01 09:00:00',
    ]);
    $orphaned = Comment::factory()->for($task)->create(['author_id' => null, 'created_at' => '2026-10-03 09:00:00']);
    Comment::factory()->create();

    $response = $this
        ->actingAs($membership->user)
        ->get(route('tasks.show', ['current_team' => $membership->team, 'task' => $task]));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('comments', fn (Collection $comments) => $comments->pluck('id')->all() === [$theirs->id, $mine->id, $orphaned->id])
        ->where('comments.0.body', 'Reproduced on Safari.')
        ->where('comments.0.author', ['id' => $colleague->id, 'name' => 'Jana Nováková'])
        ->where('comments.0.createdAt', '2026-10-01T09:00:00.000000Z')
        ->where('comments.0.can', ['update' => false, 'delete' => false])
        ->where('comments.1.body', 'Fixed in **#12**.')
        ->where('comments.1.can', ['update' => true, 'delete' => true])
        ->where('comments.2.author', null),
    );
});

test('a task whose author deleted their account has no author', function () {
    $membership = Membership::factory()->role(TeamRole::Member)->create();
    $task = Task::factory()->for(Project::factory()->for($membership->team))->create(['author_id' => null]);

    $response = $this
        ->actingAs($membership->user)
        ->get(route('tasks.show', ['current_team' => $membership->team, 'task' => $task]));

    $response->assertInertia(fn (Assert $page) => $page->where('task.author', null));
});

test('a task of another team returns 404', function () {
    $membership = Membership::factory()->role(TeamRole::Owner)->create();
    $task = Task::factory()->create();

    $response = $this
        ->actingAs($membership->user)
        ->get(route('tasks.show', ['current_team' => $membership->team, 'task' => $task]));

    $response->assertNotFound();
});

test('nobody may change a task in an archived project', function () {
    $membership = Membership::factory()->role(TeamRole::Owner)->create();
    $task = Task::factory()->for(Project::factory()->for($membership->team)->archived())->create();

    $response = $this
        ->actingAs($membership->user)
        ->get(route('tasks.show', ['current_team' => $membership->team, 'task' => $task]));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->whereNot('task.project.archivedAt', null)
        ->where('can', ['update' => false, 'delete' => false, 'createComment' => false]));
});
