<?php

declare(strict_types=1);

use App\Enums\ProjectColor;
use App\Enums\TeamRole;
use App\Models\Membership;
use App\Models\Project;
use App\Models\Task;
use Inertia\Testing\AssertableInertia as Assert;

describe('index', function () {
    test('members of any role see the active projects of the team', function () {
        $membership = Membership::factory()->role(TeamRole::Viewer)->create();
        $project = Project::factory()->for($membership->team)->create(['name' => 'Web App']);
        Project::factory()->for($membership->team)->archived()->create();
        Project::factory()->create();

        $response = $this
            ->actingAs($membership->user)
            ->get(route('projects.index', ['current_team' => $membership->team]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('projects/index')
            ->where('filter', 'active')
            ->has('projects', 1)
            ->where('projects.0.id', $project->id)
            ->where('projects.0.name', 'Web App'),
        );
    });

    test('the archived filter lists only archived projects', function () {
        $membership = Membership::factory()->role(TeamRole::Viewer)->create();
        Project::factory()->for($membership->team)->create();
        $archived = Project::factory()->for($membership->team)->archived()->create();

        $response = $this
            ->actingAs($membership->user)
            ->get(route('projects.index', ['current_team' => $membership->team, 'filter' => 'archived']));

        $response->assertInertia(fn (Assert $page) => $page
            ->where('filter', 'archived')
            ->has('projects', 1)
            ->where('projects.0.id', $archived->id),
        );
    });

    test('the create dialog suggests the first colour no active project uses', function () {
        $membership = Membership::factory()->role(TeamRole::Owner)->create();
        Project::factory()->for($membership->team)->create(['color' => ProjectColor::Slate]);
        Project::factory()->for($membership->team)->create(['color' => ProjectColor::Orange]);
        Project::factory()->for($membership->team)->archived()->create(['color' => ProjectColor::Red]);

        $response = $this
            ->actingAs($membership->user)
            ->get(route('projects.index', ['current_team' => $membership->team]));

        $response->assertInertia(fn (Assert $page) => $page->where('suggestedColor', 'red'));
    });

    test('the create dialog suggests the first colour once every colour is in use', function () {
        $membership = Membership::factory()->role(TeamRole::Owner)->create();
        foreach (ProjectColor::cases() as $color) {
            Project::factory()->for($membership->team)->create(['color' => $color]);
        }

        $response = $this
            ->actingAs($membership->user)
            ->get(route('projects.index', ['current_team' => $membership->team]));

        $response->assertInertia(fn (Assert $page) => $page->where('suggestedColor', 'slate'));
    });
});

describe('store', function () {
    test('owners and admins create a project', function (TeamRole $role) {
        $membership = Membership::factory()->role($role)->create();

        $response = $this
            ->actingAs($membership->user)
            ->post(route('projects.store', ['current_team' => $membership->team]), [
                'name' => 'Web App',
                'identifier' => 'WEB',
                'color' => 'sky',
                'description' => 'Customer-facing web application.',
            ]);

        $response->assertRedirect(route('projects.index', ['current_team' => $membership->team]));

        $project = $membership->team->projects()->sole();
        expect($project)
            ->name->toBe('Web App')
            ->identifier->toBe('WEB')
            ->color->toBe(ProjectColor::Sky)
            ->description->toBe('Customer-facing web application.')
            ->archived_at->toBeNull()
            ->next_task_number->toBe(1);
    })->with([TeamRole::Owner, TeamRole::Admin]);

    test('members and viewers cannot create a project', function (TeamRole $role) {
        $membership = Membership::factory()->role($role)->create();

        $response = $this
            ->actingAs($membership->user)
            ->post(route('projects.store', ['current_team' => $membership->team]), [
                'name' => 'Web App',
                'identifier' => 'WEB',
                'color' => 'sky',
            ]);

        $response->assertForbidden();
        expect($membership->team->projects()->exists())->toBeFalse();
    })->with([TeamRole::Member, TeamRole::Viewer]);

    test('name, identifier and colour are required', function () {
        $membership = Membership::factory()->role(TeamRole::Owner)->create();

        $response = $this
            ->actingAs($membership->user)
            ->post(route('projects.store', ['current_team' => $membership->team]), []);

        $response->assertInvalid([
            'name' => 'The name field is required.',
            'identifier' => 'The identifier field is required.',
            'color' => 'The color field is required.',
        ]);
    });

    test('the identifier must be 2 to 5 uppercase letters', function (string $identifier) {
        $membership = Membership::factory()->role(TeamRole::Owner)->create();

        $response = $this
            ->actingAs($membership->user)
            ->post(route('projects.store', ['current_team' => $membership->team]), [
                'name' => 'Web App',
                'identifier' => $identifier,
                'color' => 'sky',
            ]);

        $response->assertInvalid(['identifier' => 'The identifier must be 2 to 5 uppercase letters.']);
    })->with([
        'one letter' => 'W',
        'six letters' => 'WEBAPP',
        'lowercase' => 'web',
        'digits' => 'WEB2',
    ]);

    test('the identifier must be unique within the team', function () {
        $membership = Membership::factory()->role(TeamRole::Owner)->create();
        Project::factory()->for($membership->team)->archived()->create(['identifier' => 'WEB']);

        $response = $this
            ->actingAs($membership->user)
            ->post(route('projects.store', ['current_team' => $membership->team]), [
                'name' => 'Web App',
                'identifier' => 'WEB',
                'color' => 'sky',
            ]);

        $response->assertInvalid(['identifier' => 'Another project in this team already uses this identifier.']);
    });

    test('another team may use the same identifier', function () {
        $membership = Membership::factory()->role(TeamRole::Owner)->create();
        Project::factory()->create(['identifier' => 'WEB']);

        $response = $this
            ->actingAs($membership->user)
            ->post(route('projects.store', ['current_team' => $membership->team]), [
                'name' => 'Web App',
                'identifier' => 'WEB',
                'color' => 'sky',
            ]);

        $response->assertValid();
        expect($membership->team->projects()->sole()->identifier)->toBe('WEB');
    });

    test('the colour must come from the palette', function () {
        $membership = Membership::factory()->role(TeamRole::Owner)->create();

        $response = $this
            ->actingAs($membership->user)
            ->post(route('projects.store', ['current_team' => $membership->team]), [
                'name' => 'Web App',
                'identifier' => 'WEB',
                'color' => 'black',
            ]);

        $response->assertInvalid(['color' => 'The selected color is invalid.']);
    });
});

describe('update', function () {
    test('owners and admins update a project', function (TeamRole $role) {
        $membership = Membership::factory()->role($role)->create();
        $project = Project::factory()->for($membership->team)->create(['identifier' => 'WEB']);

        $response = $this
            ->actingAs($membership->user)
            ->from(route('projects.index', ['current_team' => $membership->team]))
            ->patch(route('projects.update', ['current_team' => $membership->team, 'project' => $project]), [
                'name' => 'Storefront',
                'identifier' => 'SHOP',
                'color' => 'pink',
                'description' => null,
            ]);

        $response->assertRedirect(route('projects.index', ['current_team' => $membership->team]));

        expect($project->fresh())
            ->name->toBe('Storefront')
            ->identifier->toBe('SHOP')
            ->color->toBe(ProjectColor::Pink)
            ->description->toBeNull();
    })->with([TeamRole::Owner, TeamRole::Admin]);

    test('a project keeps its own identifier', function () {
        $membership = Membership::factory()->role(TeamRole::Owner)->create();
        $project = Project::factory()->for($membership->team)->create(['identifier' => 'WEB']);

        $response = $this
            ->actingAs($membership->user)
            ->patch(route('projects.update', ['current_team' => $membership->team, 'project' => $project]), [
                'name' => 'Storefront',
                'identifier' => 'WEB',
                'color' => 'pink',
            ]);

        $response->assertValid();
        expect($project->fresh()->name)->toBe('Storefront');
    });

    test('the identifier cannot take one used by another project of the team', function () {
        $membership = Membership::factory()->role(TeamRole::Owner)->create();
        Project::factory()->for($membership->team)->create(['identifier' => 'API']);
        $project = Project::factory()->for($membership->team)->create(['identifier' => 'WEB']);

        $response = $this
            ->actingAs($membership->user)
            ->patch(route('projects.update', ['current_team' => $membership->team, 'project' => $project]), [
                'name' => 'Web App',
                'identifier' => 'API',
                'color' => 'pink',
            ]);

        $response->assertInvalid(['identifier' => 'Another project in this team already uses this identifier.']);
        expect($project->fresh()->identifier)->toBe('WEB');
    });

    test('members and viewers cannot update a project', function (TeamRole $role) {
        $membership = Membership::factory()->role($role)->create();
        $project = Project::factory()->for($membership->team)->create(['name' => 'Web App']);

        $response = $this
            ->actingAs($membership->user)
            ->patch(route('projects.update', ['current_team' => $membership->team, 'project' => $project]), [
                'name' => 'Storefront',
                'identifier' => $project->identifier,
                'color' => 'pink',
            ]);

        $response->assertForbidden();
        expect($project->fresh()->name)->toBe('Web App');
    })->with([TeamRole::Member, TeamRole::Viewer]);

    test('an archived project cannot be updated', function () {
        $membership = Membership::factory()->role(TeamRole::Owner)->create();
        $project = Project::factory()->for($membership->team)->archived()->create(['name' => 'Web App']);

        $response = $this
            ->actingAs($membership->user)
            ->patch(route('projects.update', ['current_team' => $membership->team, 'project' => $project]), [
                'name' => 'Storefront',
                'identifier' => $project->identifier,
                'color' => 'pink',
            ]);

        $response->assertForbidden();
        expect($project->fresh()->name)->toBe('Web App');
    });

    test('a project of another team returns 404', function () {
        $membership = Membership::factory()->role(TeamRole::Owner)->create();
        $project = Project::factory()->create(['name' => 'Web App']);

        $response = $this
            ->actingAs($membership->user)
            ->patch(route('projects.update', ['current_team' => $membership->team, 'project' => $project]), [
                'name' => 'Storefront',
                'identifier' => 'SHOP',
                'color' => 'pink',
            ]);

        $response->assertNotFound();
        expect($project->fresh()->name)->toBe('Web App');
    });
});

describe('destroy', function () {
    test('owners delete a project with its tasks after typing its identifier', function () {
        $membership = Membership::factory()->role(TeamRole::Owner)->create();
        $project = Project::factory()->for($membership->team)->create(['identifier' => 'WEB']);
        $task = Task::factory()->for($project)->create();
        $otherTask = Task::factory()->create();

        $response = $this
            ->actingAs($membership->user)
            ->delete(route('projects.destroy', ['current_team' => $membership->team, 'project' => $project]), [
                'identifier' => 'WEB',
            ]);

        $response->assertRedirect(route('projects.index', ['current_team' => $membership->team]));
        expect($project->fresh())->toBeNull()
            ->and($task->fresh())->toBeNull()
            ->and($otherTask->fresh())->not->toBeNull();
    });

    test('the typed identifier must match the project', function () {
        $membership = Membership::factory()->role(TeamRole::Owner)->create();
        $project = Project::factory()->for($membership->team)->create(['identifier' => 'WEB']);

        $response = $this
            ->actingAs($membership->user)
            ->delete(route('projects.destroy', ['current_team' => $membership->team, 'project' => $project]), [
                'identifier' => 'API',
            ]);

        $response->assertInvalid(['identifier' => 'The project identifier does not match.']);
        expect($project->fresh())->not->toBeNull();
    });

    test('admins, members and viewers cannot delete a project', function (TeamRole $role) {
        $membership = Membership::factory()->role($role)->create();
        $project = Project::factory()->for($membership->team)->create(['identifier' => 'WEB']);

        $response = $this
            ->actingAs($membership->user)
            ->delete(route('projects.destroy', ['current_team' => $membership->team, 'project' => $project]), [
                'identifier' => 'WEB',
            ]);

        $response->assertForbidden();
        expect($project->fresh())->not->toBeNull();
    })->with([TeamRole::Admin, TeamRole::Member, TeamRole::Viewer]);

    test('a project of another team returns 404', function () {
        $membership = Membership::factory()->role(TeamRole::Owner)->create();
        $project = Project::factory()->create(['identifier' => 'WEB']);

        $response = $this
            ->actingAs($membership->user)
            ->delete(route('projects.destroy', ['current_team' => $membership->team, 'project' => $project]), [
                'identifier' => 'WEB',
            ]);

        $response->assertNotFound();
        expect($project->fresh())->not->toBeNull();
    });
});

describe('show', function () {
    test('members of any role open a project', function () {
        $membership = Membership::factory()->role(TeamRole::Viewer)->create();
        $project = Project::factory()->for($membership->team)->archived()->create(['identifier' => 'WEB']);

        $response = $this
            ->actingAs($membership->user)
            ->get(route('projects.show', ['current_team' => $membership->team, 'project' => $project]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('projects/show')
            ->where('project.id', $project->id)
            ->where('project.identifier', 'WEB')
            ->where('project.archivedAt', $project->archived_at->toISOString()),
        );
    });

    test('a project of another team returns 404', function () {
        $membership = Membership::factory()->role(TeamRole::Owner)->create();
        $project = Project::factory()->create();

        $response = $this
            ->actingAs($membership->user)
            ->get(route('projects.show', ['current_team' => $membership->team, 'project' => $project]));

        $response->assertNotFound();
    });
});
