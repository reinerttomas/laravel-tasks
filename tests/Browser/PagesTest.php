<?php

declare(strict_types=1);

use App\Enums\TeamRole;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;

test('guest pages render without javascript errors', function () {
    $pages = visit([
        route('home', absolute: false),
        route('login', absolute: false),
        route('register', absolute: false),
        route('password.request', absolute: false),
    ]);

    $pages->assertNoSmoke();
});

test('authenticated pages render without javascript errors', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $project = Project::factory()->for($team)->create();
    Task::factory()->for($project)->for($user, 'assignee')->create(['due_date' => '2020-01-01']);

    $this->actingAs($user);

    $pages = visit([
        route('dashboard', ['current_team' => $user->personalTeam()->slug], absolute: false),
        route('profile.edit', absolute: false),
        route('security.edit', absolute: false),
        route('appearance.edit', absolute: false),
        route('teams.index', absolute: false),
        route('teams.edit', ['team' => $team->slug], absolute: false),
        route('projects.index', ['current_team' => $team], absolute: false),
        route('projects.index', ['current_team' => $team, 'filter' => 'archived'], absolute: false),
        route('projects.show', ['current_team' => $team, 'project' => $project], absolute: false),
    ]);

    $pages->assertNoSmoke();
});
