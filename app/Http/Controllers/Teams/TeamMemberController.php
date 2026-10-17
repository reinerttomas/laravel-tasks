<?php

declare(strict_types=1);

namespace App\Http\Controllers\Teams;

use App\Actions\RemoveTeamMember;
use App\Actions\UpdateTeamMember;
use App\Enums\TeamRole;
use App\Http\Requests\Teams\UpdateTeamMemberRequest;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

final class TeamMemberController
{
    /**
     * Update the specified team member's role.
     */
    public function update(UpdateTeamMemberRequest $request, Team $team, User $user, UpdateTeamMember $updateTeamMember): RedirectResponse
    {
        Gate::authorize('updateMember', $team);

        $updateTeamMember->handle($team, $user, TeamRole::from($request->validated('role')));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Member role updated.')]);

        return to_route('teams.edit', ['team' => $team->slug]);
    }

    /**
     * Remove the specified team member.
     */
    public function destroy(Team $team, User $user, RemoveTeamMember $removeTeamMember): RedirectResponse
    {
        Gate::authorize('removeMember', $team);

        abort_if($team->owner()?->is($user), 403, __('The team owner cannot be removed.'));

        $removeTeamMember->handle($team, $user);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Member removed.')]);

        return to_route('teams.edit', ['team' => $team->slug]);
    }
}
