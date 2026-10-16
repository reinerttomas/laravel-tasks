<?php

declare(strict_types=1);

use App\Enums\TeamRole;
use App\Models\Membership;
use App\Models\Team;
use App\Models\User;

test('a membership belongs to its team and user', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Admin->value]);

    $membership = Membership::query()
        ->where('team_id', $team->id)
        ->where('user_id', $user->id)
        ->sole();

    expect($membership->team->is($team))->toBeTrue()
        ->and($membership->user->is($user))->toBeTrue();
});

test('the membership factory makes a member of a new team', function () {
    $membership = Membership::factory()->create();

    expect($membership->role)->toBe(TeamRole::Member)
        ->and($membership->team->members()->whereKey($membership->user_id)->exists())->toBeTrue();
});
