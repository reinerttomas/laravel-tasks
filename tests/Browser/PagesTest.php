<?php

declare(strict_types=1);

use App\Enums\TeamRole;
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

    $this->actingAs($user);

    $pages = visit([
        route('dashboard', ['current_team' => $user->personalTeam()->slug], absolute: false),
        route('profile.edit', absolute: false),
        route('security.edit', absolute: false),
        route('appearance.edit', absolute: false),
        route('teams.index', absolute: false),
        route('teams.edit', ['team' => $team->slug], absolute: false),
    ]);

    $pages->assertNoSmoke();
});
