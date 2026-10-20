<?php

declare(strict_types=1);

use App\Models\User;

test('guests are redirected to the login page', function () {
    $this->get(route('home'))->assertRedirect(route('login'));
});

test('authenticated users are redirected to their current team dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('home'))
        ->assertRedirect(route('dashboard', ['current_team' => $user->currentTeam]));
});
