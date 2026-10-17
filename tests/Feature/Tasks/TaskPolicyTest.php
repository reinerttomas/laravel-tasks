<?php

declare(strict_types=1);

use App\Enums\TeamRole;
use App\Models\Membership;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;

/**
 * Create a member with the given role and a task in their team, authored by someone else unless stated.
 *
 * @return array{User, Task}
 */
function memberWithTask(TeamRole $role, bool $authoredByMember = false, bool $archived = false): array
{
    $membership = Membership::factory()->role($role)->create();
    $project = Project::factory()->for($membership->team)
        ->when($archived, fn ($factory) => $factory->archived())
        ->create();
    $task = Task::factory()->for($project)
        ->when($authoredByMember, fn ($factory) => $factory->for($membership->user, 'author'))
        ->create();

    return [$membership->user, $task];
}

test('who can create and update tasks', function (TeamRole $role, bool $allowed) {
    [$user, $task] = memberWithTask($role);

    expect($user->can('create', [Task::class, $task->project]))->toBe($allowed)
        ->and($user->can('update', $task))->toBe($allowed);
})->with([
    'owner' => [TeamRole::Owner, true],
    'admin' => [TeamRole::Admin, true],
    'member' => [TeamRole::Member, true],
    'viewer' => [TeamRole::Viewer, false],
]);

test('who can delete a task written by someone else', function (TeamRole $role, bool $allowed) {
    [$user, $task] = memberWithTask($role);

    expect($user->can('delete', $task))->toBe($allowed);
})->with([
    'owner' => [TeamRole::Owner, true],
    'admin' => [TeamRole::Admin, true],
    'member' => [TeamRole::Member, false],
    'viewer' => [TeamRole::Viewer, false],
]);

test('who can delete their own task', function (TeamRole $role, bool $allowed) {
    [$user, $task] = memberWithTask($role, authoredByMember: true);

    expect($user->can('delete', $task))->toBe($allowed);
})->with([
    'member' => [TeamRole::Member, true],
    'viewer demoted after writing it' => [TeamRole::Viewer, false],
]);

test('nothing in an archived project can be changed, even by the owner', function () {
    [$user, $task] = memberWithTask(TeamRole::Owner, authoredByMember: true, archived: true);

    expect($user->can('create', [Task::class, $task->project]))->toBeFalse()
        ->and($user->can('update', $task))->toBeFalse()
        ->and($user->can('delete', $task))->toBeFalse();
});
