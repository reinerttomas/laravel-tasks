<?php

declare(strict_types=1);

namespace App\Http\Controllers\Teams;

use App\Actions\SwitchTeam;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class SwitchTeamController
{
    /**
     * Switch the user's current team and land on its dashboard, since the previous page may belong to the old team.
     */
    public function __invoke(Request $request, Team $team, SwitchTeam $switchTeam): RedirectResponse
    {
        abort_unless($switchTeam->handle($request->user(), $team), 403);

        return to_route('dashboard', ['current_team' => $team->slug]);
    }
}
