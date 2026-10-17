<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final readonly class AcceptTeamInvitation
{
    public function __construct(private SwitchTeam $switchTeam) {}

    /**
     * Join the invitation's team with the invited role and make it the user's current team.
     */
    public function handle(User $user, TeamInvitation $invitation): void
    {
        DB::transaction(function () use ($user, $invitation): void {
            $team = $invitation->team;

            $team->memberships()->firstOrCreate(
                ['user_id' => $user->id],
                ['role' => $invitation->role],
            );

            $invitation->update(['accepted_at' => now()]);

            $this->switchTeam->handle($user, $team);
        });
    }
}
