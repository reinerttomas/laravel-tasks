<?php

declare(strict_types=1);

use App\Enums\TaskStatus;
use App\Enums\TeamRole;
use App\Models\Membership;
use App\Models\Project;
use App\Models\Task;

test('members create a task from the project task list', function () {
    $membership = Membership::factory()->role(TeamRole::Member)->create();
    $project = Project::factory()->for($membership->team)->create(['identifier' => 'WEB']);
    $this->actingAs($membership->user);

    $page = visit(route('projects.show', ['current_team' => $membership->team, 'project' => $project], absolute: false));

    $page->assertSee('No tasks yet.')
        ->click('@new-task-button')
        ->fill('title', 'Fix the login form')
        ->click('@task-submit')
        ->assertSee('WEB-1 created.')
        ->assertSeeIn('@task-row', 'Fix the login form')
        ->assertNoJavaScriptErrors();

    expect($project->tasks()->sole())
        ->title->toBe('Fix the login form')
        ->author_id->toBe($membership->user->id);
});

test('the toolbar filters, searches and resets the task list', function () {
    $membership = Membership::factory()->role(TeamRole::Viewer)->create();
    $project = Project::factory()->for($membership->team)->create(['identifier' => 'WEB']);
    Task::factory()->for($project)->create(['title' => 'Fix the login form', 'status' => TaskStatus::Todo]);
    Task::factory()->for($project)->create(['title' => 'Write release notes', 'status' => TaskStatus::Done]);
    $this->actingAs($membership->user);

    $page = visit(route('projects.show', ['current_team' => $membership->team, 'project' => $project], absolute: false));

    $page->assertSee('Showing 1–2 of 2')
        ->click('@facet-status')
        ->click('@facet-option-done')
        ->assertSee('Showing 1–1 of 1')
        ->assertDontSee('Fix the login form')
        ->assertQueryStringHas('status')
        ->click('@reset-filters')
        ->assertSee('Showing 1–2 of 2')
        ->fill('search', 'WEB-1')
        ->assertSee('Showing 1–1 of 1')
        ->assertDontSee('Write release notes')
        ->assertNoJavaScriptErrors();
});
