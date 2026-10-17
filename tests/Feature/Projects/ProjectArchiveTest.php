<?php

declare(strict_types=1);

use App\Enums\TeamRole;
use App\Models\Membership;
use App\Models\Project;

describe('archive', function () {
    test('owners and admins archive a project', function (TeamRole $role) {
        $membership = Membership::factory()->role($role)->create();
        $project = Project::factory()->for($membership->team)->create();

        $response = $this
            ->actingAs($membership->user)
            ->from(route('projects.index', ['current_team' => $membership->team]))
            ->post(route('projects.archive', ['current_team' => $membership->team, 'project' => $project]));

        $response->assertRedirect(route('projects.index', ['current_team' => $membership->team]));
        expect($project->fresh()->archived_at)->not->toBeNull();
    })->with([TeamRole::Owner, TeamRole::Admin]);

    test('members and viewers cannot archive a project', function (TeamRole $role) {
        $membership = Membership::factory()->role($role)->create();
        $project = Project::factory()->for($membership->team)->create();

        $response = $this
            ->actingAs($membership->user)
            ->post(route('projects.archive', ['current_team' => $membership->team, 'project' => $project]));

        $response->assertForbidden();
        expect($project->fresh()->archived_at)->toBeNull();
    })->with([TeamRole::Member, TeamRole::Viewer]);

    test('an archived project cannot be archived again', function () {
        $this->travelTo(now()->subWeek());
        $membership = Membership::factory()->role(TeamRole::Owner)->create();
        $project = Project::factory()->for($membership->team)->archived()->create();
        $archivedAt = $project->archived_at;
        $this->travelBack();

        $response = $this
            ->actingAs($membership->user)
            ->post(route('projects.archive', ['current_team' => $membership->team, 'project' => $project]));

        $response->assertForbidden();
        expect($project->fresh()->archived_at->equalTo($archivedAt))->toBeTrue();
    });

    test('a project of another team returns 404', function () {
        $membership = Membership::factory()->role(TeamRole::Owner)->create();
        $project = Project::factory()->create();

        $response = $this
            ->actingAs($membership->user)
            ->post(route('projects.archive', ['current_team' => $membership->team, 'project' => $project]));

        $response->assertNotFound();
        expect($project->fresh()->archived_at)->toBeNull();
    });
});

describe('restore', function () {
    test('owners and admins restore an archived project', function (TeamRole $role) {
        $membership = Membership::factory()->role($role)->create();
        $project = Project::factory()->for($membership->team)->archived()->create();

        $response = $this
            ->actingAs($membership->user)
            ->from(route('projects.index', ['current_team' => $membership->team, 'filter' => 'archived']))
            ->delete(route('projects.restore', ['current_team' => $membership->team, 'project' => $project]));

        $response->assertRedirect(route('projects.index', ['current_team' => $membership->team, 'filter' => 'archived']));
        expect($project->fresh()->archived_at)->toBeNull();
    })->with([TeamRole::Owner, TeamRole::Admin]);

    test('members and viewers cannot restore a project', function (TeamRole $role) {
        $membership = Membership::factory()->role($role)->create();
        $project = Project::factory()->for($membership->team)->archived()->create();

        $response = $this
            ->actingAs($membership->user)
            ->delete(route('projects.restore', ['current_team' => $membership->team, 'project' => $project]));

        $response->assertForbidden();
        expect($project->fresh()->archived_at)->not->toBeNull();
    })->with([TeamRole::Member, TeamRole::Viewer]);

    test('an active project cannot be restored', function () {
        $membership = Membership::factory()->role(TeamRole::Owner)->create();
        $project = Project::factory()->for($membership->team)->create();

        $response = $this
            ->actingAs($membership->user)
            ->delete(route('projects.restore', ['current_team' => $membership->team, 'project' => $project]));

        $response->assertForbidden();
    });

    test('a project of another team returns 404', function () {
        $membership = Membership::factory()->role(TeamRole::Owner)->create();
        $project = Project::factory()->archived()->create();

        $response = $this
            ->actingAs($membership->user)
            ->delete(route('projects.restore', ['current_team' => $membership->team, 'project' => $project]));

        $response->assertNotFound();
        expect($project->fresh()->archived_at)->not->toBeNull();
    });
});
