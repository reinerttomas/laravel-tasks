<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\TeamInvitation;

final readonly class DeleteTeamInvitation
{
    /**
     * Delete the invitation, whether the team cancels it or the invitee declines it.
     */
    public function handle(TeamInvitation $invitation): void
    {
        $invitation->delete();
    }
}
