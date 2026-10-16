<?php

declare(strict_types=1);

use App\Models\Team;
use App\Models\User;

test('users cannot switch to a team they do not belong to', function () {
    $user = User::factory()->create();
    $personalTeamId = $user->current_team_id;
    $team = Team::factory()->create();

    expect($user->switchTeam($team))->toBeFalse()
        ->and($user->fresh()->current_team_id)->toBe($personalTeamId);
});
