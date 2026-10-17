<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Notifications\Teams\TeamInvitation as TeamInvitationNotification;
use Illuminate\Support\Facades\Notification;

final readonly class CreateTeamInvitation
{
    /**
     * Invite the email address to join the team and send the invitation.
     */
    public function handle(Team $team, User $inviter, string $email, TeamRole $role): TeamInvitation
    {
        $invitation = $team->invitations()->create([
            'email' => $email,
            'role' => $role,
            'invited_by' => $inviter->id,
            'expires_at' => now()->addDays(3),
        ]);

        Notification::route('mail', $invitation->email)
            ->notify(new TeamInvitationNotification($invitation));

        return $invitation;
    }
}
