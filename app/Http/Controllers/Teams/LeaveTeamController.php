<?php

declare(strict_types=1);

namespace App\Http\Controllers\Teams;

use App\Actions\LeaveTeam;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

final class LeaveTeamController
{
    /**
     * Leave the specified team.
     */
    public function __invoke(Request $request, Team $team, LeaveTeam $leaveTeam): RedirectResponse
    {
        Gate::authorize('leave', $team);

        $leaveTeam->handle($request->user(), $team);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('You left the team ":name"', ['name' => $team->name])]);

        return to_route('teams.index');
    }
}
