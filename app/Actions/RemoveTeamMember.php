<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Team;
use App\Models\User;

final readonly class RemoveTeamMember
{
    public function __construct(
        private DeleteMembership $deleteMembership,
        private SwitchTeam $switchTeam,
    ) {}

    /**
     * Remove the member from the team, sending them back to their personal team.
     *
     * Their tasks in the team become unassigned; they stay the author.
     */
    public function handle(Team $team, User $member): void
    {
        $this->deleteMembership->handle($team, $member);

        if ($member->isCurrentTeam($team)) {
            $this->switchTeam->handle($member, $member->personalTeam());
        }
    }
}
