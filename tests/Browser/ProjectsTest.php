<?php

declare(strict_types=1);

use App\Enums\ProjectColor;
use App\Enums\TeamRole;
use App\Models\Membership;
use App\Models\Project;

test('owners create a project with an identifier suggested from its name', function () {
    $membership = Membership::factory()->role(TeamRole::Owner)->create();
    $this->actingAs($membership->user);

    $page = visit(route('projects.index', ['current_team' => $membership->team], absolute: false));

    $page->click('@new-project-button')
        ->fill('name', 'Customer Portal')
        ->assertValue('identifier', 'CP')
        ->click('@project-submit')
        ->assertSee('Customer Portal created.')
        ->assertNoJavaScriptErrors();

    expect($membership->team->projects()->sole())
        ->name->toBe('Customer Portal')
        ->identifier->toBe('CP')
        ->color->toBe(ProjectColor::Slate);
});

test('owners delete a project only after typing its identifier', function () {
    $membership = Membership::factory()->role(TeamRole::Owner)->create();
    $project = Project::factory()->for($membership->team)->create(['name' => 'Web App', 'identifier' => 'WEB']);
    $this->actingAs($membership->user);

    $page = visit(route('projects.index', ['current_team' => $membership->team], absolute: false));

    $page->click('@project-actions')
        ->click('Delete')
        ->assertButtonDisabled('@delete-project-confirm')
        ->fill('identifier', 'web')
        ->click('@delete-project-confirm')
        ->assertSee('Web App deleted.')
        ->assertSee('No active projects.')
        ->assertNoJavaScriptErrors();

    expect($project->fresh())->toBeNull();
});
