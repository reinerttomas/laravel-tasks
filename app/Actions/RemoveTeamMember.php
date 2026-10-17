<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Team;
use App\Models\User;

final readonly class RemoveTeamMember
{
    public function __construct(private SwitchTeam $switchTeam) {}

    /**
     * Remove the member from the team, sending them back to their personal team.
     */
    public function handle(Team $team, User $member): void
    {
        $team->memberships()
            ->where('user_id', $member->id)
            ->delete();

        if ($member->isCurrentTeam($team)) {
            $this->switchTeam->handle($member, $member->personalTeam());
        }
    }
}
