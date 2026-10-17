<?php

declare(strict_types=1);

use App\Actions\SwitchTeam;
use App\Models\Team;
use App\Models\User;

test('users cannot switch to a team they do not belong to', function () {
    $user = User::factory()->create();
    $personalTeamId = $user->current_team_id;
    $team = Team::factory()->create();

    expect(app(SwitchTeam::class)->handle($user, $team))->toBeFalse()
        ->and($user->fresh()->current_team_id)->toBe($personalTeamId);
});
