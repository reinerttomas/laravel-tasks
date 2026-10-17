<?php

declare(strict_types=1);

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Enums\TeamRole;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;

test('each demo user logs in to the demo team with their role', function (string $email, TeamRole $role) {
    $this->seed();

    $team = Team::query()->where('slug', 'acme-inc')->firstOrFail();

    $response = $this->post(route('login.store'), [
        'email' => $email,
        'password' => 'password',
    ]);

    $user = User::query()->where('email', $email)->firstOrFail();

    $this->assertAuthenticatedAs($user);
    $response->assertRedirect(route('dashboard', ['current_team' => 'acme-inc']));
    expect($user->teamRole($team))->toBe($role);
})->with([
    'owner' => ['owner@example.com', TeamRole::Owner],
    'admin' => ['admin@example.com', TeamRole::Admin],
    'member' => ['member@example.com', TeamRole::Member],
    'viewer' => ['viewer@example.com', TeamRole::Viewer],
]);

test('the demo team has three projects, one archived, each numbering its tasks from one', function () {
    $this->seed();

    $projects = Team::query()->where('slug', 'acme-inc')->firstOrFail()->projects()->with('tasks')->get();

    expect($projects)->toHaveCount(3)
        ->and($projects->whereNotNull('archived_at'))->toHaveCount(1);

    foreach ($projects as $project) {
        $numbers = $project->tasks->pluck('number')->sort()->values()->all();

        expect($numbers)->toBe(range(1, count($numbers)))
            ->and($project->next_task_number)->toBe(count($numbers) + 1);
    }
});

test('the demo tasks spread across every status, priority and assignable member', function () {
    $this->seed();

    $tasks = Team::query()->where('slug', 'acme-inc')->firstOrFail()->tasks()->with('assignee')->get();

    expect($tasks->count())->toBeGreaterThanOrEqual(45)
        ->and($tasks->pluck('status')->unique()->values()->all())
        ->toEqualCanonicalizing(TaskStatus::cases())
        ->and($tasks->pluck('priority')->unique()->values()->all())
        ->toEqualCanonicalizing(TaskPriority::cases())
        ->and($tasks->pluck('assignee.email')->unique()->values()->all())
        ->toEqualCanonicalizing(['owner@example.com', 'admin@example.com', 'member@example.com', null]);
});

test('the demo tasks have comment threads by different authors, posted after the task', function () {
    $this->seed();

    $tasks = Team::query()->where('slug', 'acme-inc')->firstOrFail()->tasks()->with('comments.author')->get();
    $comments = $tasks->flatMap->comments;

    expect($comments->pluck('author.email')->unique()->all())
        ->toEqualCanonicalizing(['owner@example.com', 'admin@example.com', 'member@example.com'])
        ->and($tasks->filter(fn (Task $task): bool => $task->comments->pluck('author_id')->unique()->count() > 1))
        ->not->toBeEmpty();

    foreach ($tasks as $task) {
        foreach ($task->comments as $comment) {
            expect($comment->created_at->isAfter($task->created_at))->toBeTrue();
        }
    }
});
