<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;

final readonly class UpdateTeamMember
{
    /**
     * Change the member's role in the team.
     */
    public function handle(Team $team, User $member, TeamRole $role): void
    {
        $team->memberships()
            ->where('user_id', $member->id)
            ->firstOrFail()
            ->update(['role' => $role]);
    }
}
