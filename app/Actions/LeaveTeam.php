<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Team;
use App\Models\User;

final readonly class LeaveTeam
{
    public function __construct(
        private DeleteMembership $deleteMembership,
        private SwitchTeam $switchTeam,
    ) {}

    /**
     * Remove the user's own membership of the team, unassigning their tasks in it; they stay the author.
     */
    public function handle(User $user, Team $team): void
    {
        $fallbackTeam = $user->isCurrentTeam($team)
            ? $user->fallbackTeam($team)
            : null;

        $this->deleteMembership->handle($team, $user);

        if ($fallbackTeam) {
            $this->switchTeam->handle($user, $fallbackTeam);
        }
    }
}
