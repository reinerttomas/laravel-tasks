<?php

declare(strict_types=1);

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\LoginLink\Exceptions\NotAllowedInCurrentEnvironment;

test('login screen offers login links in allowed environments', function () {
    config(['login-link.allowed_environments' => ['testing']]);
    $user = User::factory()->create();

    $response = $this->get(route('login'));

    $response->assertInertia(fn (Assert $page) => $page
        ->component('auth/login')
        ->where('loginLinkUsers', [['email' => $user->email, 'role' => 'Owner', 'team' => $user->personalTeam()->name]]),
    );
});

test('login screen hides login links outside allowed environments', function () {
    User::factory()->create();

    $response = $this->get(route('login'));

    $response->assertInertia(fn (Assert $page) => $page
        ->component('auth/login')
        ->where('loginLinkUsers', []),
    );
});

test('login link logs the user in and redirects to the current team dashboard', function () {
    config(['login-link.allowed_environments' => ['testing']]);
    $user = User::factory()->create();

    $response = $this->post(route('loginLinkLogin'), ['email' => $user->email]);

    $this->assertAuthenticatedAs($user);
    $response->assertRedirect(route('dashboard', ['current_team' => $user->personalTeam()->slug]));
});

test('login link does not log in outside allowed environments', function () {
    $user = User::factory()->create();

    $this->withoutExceptionHandling();

    expect(fn () => $this->post(route('loginLinkLogin'), ['email' => $user->email]))
        ->toThrow(NotAllowedInCurrentEnvironment::class);

    $this->assertGuest();
});
