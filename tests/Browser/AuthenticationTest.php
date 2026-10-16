<?php

declare(strict_types=1);

use App\Models\User;

test('users can log in and reach their dashboard', function () {
    $user = User::factory()->create();

    $page = visit(route('login', absolute: false));

    $page->fill('email', $user->email)
        ->fill('password', 'password')
        ->click('@login-button')
        ->assertPathIs("/{$user->personalTeam()->slug}/dashboard")
        ->assertNoJavaScriptErrors();

    $this->assertAuthenticatedAs($user);
});

test('visitors can register and are asked to verify their email', function () {
    $page = visit(route('register', absolute: false));

    $page->fill('name', 'Test User')
        ->fill('email', 'test@example.com')
        ->fill('password', 'password')
        ->fill('password_confirmation', 'password')
        ->click('@register-user-button')
        ->assertPathIs(route('verification.notice', absolute: false))
        ->assertNoJavaScriptErrors();

    $user = User::query()->where('email', 'test@example.com')->sole();
    $this->assertAuthenticatedAs($user);
});
