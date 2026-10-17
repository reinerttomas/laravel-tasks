<?php

declare(strict_types=1);

use App\Enums\TaskStatus;
use App\Enums\TeamRole;
use App\Models\Membership;
use App\Models\Project;
use App\Models\Task;

test('my tasks shows open tasks by default and returns to them after a reset', function () {
    $membership = Membership::factory()->role(TeamRole::Member)->create();
    $project = Project::factory()->for($membership->team)->create(['identifier' => 'WEB']);
    Task::factory()->for($project)->for($membership->user, 'assignee')->create(['title' => 'Fix the login form', 'status' => TaskStatus::Todo]);
    Task::factory()->for($project)->for($membership->user, 'assignee')->create(['title' => 'Write release notes', 'status' => TaskStatus::Done]);
    $this->actingAs($membership->user);

    $page = visit(route('dashboard', ['current_team' => $membership->team], absolute: false));

    $page->click('My tasks')
        ->assertSee('Showing 1–1 of 1')
        ->assertSeeIn('@task-row', 'Fix the login form')
        ->assertDontSee('Write release notes')
        ->assertMissing('@reset-filters');

    $page = visit(route('my-tasks', ['current_team' => $membership->team, 'status' => ['']], absolute: false));

    $page->assertSee('Showing 1–2 of 2')
        ->click('@reset-filters')
        ->assertSee('Showing 1–1 of 1')
        ->assertDontSee('Write release notes')
        ->assertMissing('@reset-filters')
        ->click('@sort-title')
        ->assertSee('Showing 1–1 of 1')
        ->assertNoJavaScriptErrors();
});
