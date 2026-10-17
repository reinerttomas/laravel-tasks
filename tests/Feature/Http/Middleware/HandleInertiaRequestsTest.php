<?php

declare(strict_types=1);

use App\Enums\TeamRole;
use App\Models\Membership;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('pages share the permissions the member holds in the current team', function () {
    $member = User::factory()->create();
    $team = Membership::factory()->for($member)->role(TeamRole::Member)->create()->team;

    $response = $this
        ->actingAs($member)
        ->get(route('dashboard', ['current_team' => $team->slug]));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->where('currentTeamPermissions', [
            'canUpdateTeam' => false,
            'canDeleteTeam' => false,
            'canAddMember' => false,
            'canUpdateMember' => false,
            'canRemoveMember' => false,
            'canCreateInvitation' => false,
            'canCancelInvitation' => false,
            'canCreateProject' => false,
            'canUpdateProject' => false,
            'canArchiveProject' => false,
            'canDeleteProject' => false,
            'canCreateTask' => true,
            'canUpdateTask' => true,
            'canDeleteTask' => false,
            'canCreateComment' => true,
            'canDeleteComment' => false,
        ]),
    );
});
