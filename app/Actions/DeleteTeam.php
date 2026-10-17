<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final readonly class DeleteTeam
{
    public function __construct(private SwitchTeam $switchTeam) {}

    /**
     * Delete the team, moving everyone working in it to another team.
     */
    public function handle(User $user, Team $team): void
    {
        $fallbackTeam = $user->isCurrentTeam($team)
            ? $user->fallbackTeam($team)
            : null;

        DB::transaction(function () use ($user, $team): void {
            User::query()
                ->where('current_team_id', $team->id)
                ->where('id', '!=', $user->id)
                ->each(fn (User $affectedUser): bool => $this->switchTeam->handle($affectedUser, $affectedUser->personalTeam()));

            $team->invitations()->delete();
            $team->memberships()->delete();
            $team->delete();
        });

        if ($fallbackTeam) {
            $this->switchTeam->handle($user, $fallbackTeam);
        }
    }
}
