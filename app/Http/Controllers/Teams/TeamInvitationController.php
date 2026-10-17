<?php

declare(strict_types=1);

namespace App\Http\Controllers\Teams;

use App\Actions\CreateTeamInvitation;
use App\Actions\DeleteTeamInvitation;
use App\Enums\TeamRole;
use App\Http\Requests\Teams\CreateTeamInvitationRequest;
use App\Models\Team;
use App\Models\TeamInvitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

final class TeamInvitationController
{
    /**
     * Store a newly created invitation.
     */
    public function store(CreateTeamInvitationRequest $request, Team $team, CreateTeamInvitation $createTeamInvitation): RedirectResponse
    {
        Gate::authorize('inviteMember', $team);

        $createTeamInvitation->handle(
            $team,
            $request->user(),
            $request->validated('email'),
            TeamRole::from($request->validated('role')),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Invitation sent.')]);

        return to_route('teams.edit', ['team' => $team->slug]);
    }

    /**
     * Cancel the specified invitation.
     */
    public function destroy(Team $team, TeamInvitation $invitation, DeleteTeamInvitation $deleteTeamInvitation): RedirectResponse
    {
        abort_unless($invitation->team_id === $team->id, 404);

        Gate::authorize('cancelInvitation', $team);

        $deleteTeamInvitation->handle($invitation);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Invitation cancelled.')]);

        return to_route('teams.edit', ['team' => $team->slug]);
    }
}
