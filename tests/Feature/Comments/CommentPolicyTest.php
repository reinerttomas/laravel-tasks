<?php

declare(strict_types=1);

use App\Enums\TeamRole;
use App\Models\Comment;
use App\Models\Membership;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;

/**
 * Create a member with the given role and a comment in their team, written by someone else unless stated.
 *
 * @return array{User, Comment}
 */
function memberWithComment(TeamRole $role, bool $authoredByMember = false, bool $archived = false): array
{
    $membership = Membership::factory()->role($role)->create();
    $project = Project::factory()->for($membership->team)
        ->when($archived, fn ($factory) => $factory->archived())
        ->create();
    $comment = Comment::factory()->for(Task::factory()->for($project))
        ->when($authoredByMember, fn ($factory) => $factory->for($membership->user, 'author'))
        ->create();

    return [$membership->user, $comment];
}

test('who can comment', function (TeamRole $role, bool $allowed) {
    [$user, $comment] = memberWithComment($role);

    expect($user->can('create', [Comment::class, $comment->task]))->toBe($allowed);
})->with([
    'owner' => [TeamRole::Owner, true],
    'admin' => [TeamRole::Admin, true],
    'member' => [TeamRole::Member, true],
    'viewer' => [TeamRole::Viewer, false],
]);

test('who can edit and delete a comment written by someone else', function (TeamRole $role, bool $canDelete) {
    [$user, $comment] = memberWithComment($role);

    expect($user->can('update', $comment))->toBeFalse()
        ->and($user->can('delete', $comment))->toBe($canDelete);
})->with([
    'owner' => [TeamRole::Owner, true],
    'admin' => [TeamRole::Admin, true],
    'member' => [TeamRole::Member, false],
    'viewer' => [TeamRole::Viewer, false],
]);

test('who can edit and delete their own comment', function (TeamRole $role, bool $allowed) {
    [$user, $comment] = memberWithComment($role, authoredByMember: true);

    expect($user->can('update', $comment))->toBe($allowed)
        ->and($user->can('delete', $comment))->toBe($allowed);
})->with([
    'owner' => [TeamRole::Owner, true],
    'admin' => [TeamRole::Admin, true],
    'member' => [TeamRole::Member, true],
    'viewer demoted after writing it' => [TeamRole::Viewer, false],
]);

test('nothing in an archived project can be commented on or changed, even by the owner', function () {
    [$user, $comment] = memberWithComment(TeamRole::Owner, authoredByMember: true, archived: true);

    expect($user->can('create', [Comment::class, $comment->task]))->toBeFalse()
        ->and($user->can('update', $comment))->toBeFalse()
        ->and($user->can('delete', $comment))->toBeFalse();
});
