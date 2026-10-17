<?php

declare(strict_types=1);

use App\Enums\TeamPermission;
use App\Enums\TeamRole;

test('each role grants the permissions of the spec matrix', function (TeamRole $role, array $expected) {
    expect($role->permissions())->toEqualCanonicalizing($expected);
})->with([
    'owner' => [TeamRole::Owner, [
        TeamPermission::UpdateTeam,
        TeamPermission::DeleteTeam,
        TeamPermission::AddMember,
        TeamPermission::UpdateMember,
        TeamPermission::RemoveMember,
        TeamPermission::CreateInvitation,
        TeamPermission::CancelInvitation,
        TeamPermission::CreateProject,
        TeamPermission::UpdateProject,
        TeamPermission::ArchiveProject,
        TeamPermission::DeleteProject,
        TeamPermission::CreateTask,
        TeamPermission::UpdateTask,
        TeamPermission::DeleteTask,
        TeamPermission::CreateComment,
        TeamPermission::DeleteComment,
    ]],
    'admin' => [TeamRole::Admin, [
        TeamPermission::UpdateTeam,
        TeamPermission::CreateInvitation,
        TeamPermission::CancelInvitation,
        TeamPermission::CreateProject,
        TeamPermission::UpdateProject,
        TeamPermission::ArchiveProject,
        TeamPermission::CreateTask,
        TeamPermission::UpdateTask,
        TeamPermission::DeleteTask,
        TeamPermission::CreateComment,
        TeamPermission::DeleteComment,
    ]],
    'member' => [TeamRole::Member, [
        TeamPermission::CreateTask,
        TeamPermission::UpdateTask,
        TeamPermission::CreateComment,
    ]],
    'viewer' => [TeamRole::Viewer, []],
]);

test('viewer is an assignable role', function () {
    expect(TeamRole::assignable())->toBe([
        ['value' => 'admin', 'label' => 'Admin'],
        ['value' => 'member', 'label' => 'Member'],
        ['value' => 'viewer', 'label' => 'Viewer'],
    ]);
});
