<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Team;
use App\Models\User;

final readonly class LeaveTeam
{
    public function __construct(private SwitchTeam $switchTeam) {}

    /**
     * Remove the user's own membership of the team.
     */
    public function handle(User $user, Team $team): void
    {
        $fallbackTeam = $user->isCurrentTeam($team)
            ? $user->fallbackTeam($team)
            : null;

        $team->memberships()
            ->where('user_id', $user->id)
            ->delete();

        if ($fallbackTeam) {
            $this->switchTeam->handle($user, $fallbackTeam);
        }
    }
}
