<?php

declare(strict_types=1);

use App\Enums\ProjectColor;
use App\Enums\TeamRole;
use App\Models\Membership;
use App\Models\Project;
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

test('pages share the active projects of the current team for the sidebar', function () {
    $member = User::factory()->create();
    $team = Membership::factory()->for($member)->role(TeamRole::Viewer)->create()->team;
    $web = Project::factory()->for($team)->create(['name' => 'Web App', 'identifier' => 'WEB', 'color' => ProjectColor::Sky]);
    $api = Project::factory()->for($team)->create(['name' => 'Public API', 'identifier' => 'API', 'color' => ProjectColor::Green]);
    Project::factory()->for($team)->archived()->create();
    Project::factory()->create();

    $response = $this
        ->actingAs($member)
        ->get(route('dashboard', ['current_team' => $team->slug]));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('currentTeamProjects', [
            ['id' => $api->id, 'name' => 'Public API', 'identifier' => 'API', 'color' => 'green'],
            ['id' => $web->id, 'name' => 'Web App', 'identifier' => 'WEB', 'color' => 'sky'],
        ]),
    );
});
