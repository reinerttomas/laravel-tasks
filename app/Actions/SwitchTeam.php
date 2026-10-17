<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\URL;

final readonly class SwitchTeam
{
    /**
     * Make the team the user's current team, provided the user belongs to it.
     */
    public function handle(User $user, Team $team): bool
    {
        if (! $user->belongsToTeam($team)) {
            return false;
        }

        $user->update(['current_team_id' => $team->id]);
        $user->setRelation('currentTeam', $team);

        URL::defaults(['current_team' => $team->slug]);

        return true;
    }
}
