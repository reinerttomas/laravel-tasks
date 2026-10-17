<?php

declare(strict_types=1);

use App\Enums\TaskStatus;
use App\Enums\TeamRole;
use App\Models\Membership;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;

test('the dashboard greets the member and lists their overdue work', function () {
    $user = User::factory()->create(['name' => 'Jana Nováková']);
    $membership = Membership::factory()->for($user)->role(TeamRole::Member)->create();
    $project = Project::factory()->for($membership->team)->create(['identifier' => 'WEB']);
    $task = Task::factory()->for($project)->for($user, 'assignee')->create([
        'title' => 'Fix the login form',
        'status' => TaskStatus::InProgress,
        'due_date' => today()->subDay(),
    ]);
    $this->actingAs($user);

    $page = visit(route('dashboard', ['current_team' => $membership->team], absolute: false));

    $page->assertSee('Jana')
        ->assertSee('You have 0 tasks in progress and 1 overdue.')
        ->assertSeeIn('@section-overdue', 'Fix the login form')
        ->assertSeeIn('@section-overdue', 'WEB-1')
        ->assertSeeIn('@section-up-next', 'No tasks waiting for you.')
        ->click('@task-link')
        ->assertPathIs(route('tasks.show', ['current_team' => $membership->team, 'task' => $task], absolute: false))
        ->assertNoJavaScriptErrors();
});
