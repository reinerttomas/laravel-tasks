<?php

declare(strict_types=1);

namespace App\Http\Controllers\Teams;

use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class SwitchTeamController
{
    /**
     * Switch the user's current team.
     */
    public function __invoke(Request $request, Team $team): RedirectResponse
    {
        abort_unless($request->user()->belongsToTeam($team), 403);

        $request->user()->switchTeam($team);

        return back();
    }
}
