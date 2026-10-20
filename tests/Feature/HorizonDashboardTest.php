<?php

declare(strict_types=1);

use App\Models\User;

test('listed user can open the horizon dashboard in production', function (): void {
    app()->detectEnvironment(fn (): string => 'production');
    config(['horizon.allowed_emails' => ['other@example.com', 'admin@example.com']]);
    $user = User::factory()->create(['email' => 'admin@example.com']);

    $response = $this->actingAs($user)->get(route('horizon.index'));

    $response->assertOk();
});

test('unlisted user is refused the horizon dashboard in production', function (): void {
    app()->detectEnvironment(fn (): string => 'production');
    config(['horizon.allowed_emails' => ['admin@example.com']]);
    $user = User::factory()->create(['email' => 'someone@example.com']);

    $response = $this->actingAs($user)->get(route('horizon.index'));

    $response->assertForbidden();
});

test('guest is refused the horizon dashboard in production', function (): void {
    app()->detectEnvironment(fn (): string => 'production');
    config(['horizon.allowed_emails' => ['admin@example.com']]);

    $response = $this->get(route('horizon.index'));

    $response->assertForbidden();
});

test('any visitor can open the horizon dashboard locally', function (): void {
    app()->detectEnvironment(fn (): string => 'local');

    $response = $this->get(route('horizon.index'));

    $response->assertOk();
});
