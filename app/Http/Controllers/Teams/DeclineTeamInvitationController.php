<?php

declare(strict_types=1);

namespace App\Http\Controllers\Teams;

use App\Actions\DeleteTeamInvitation;
use App\Http\Requests\Teams\RespondToTeamInvitationRequest;
use App\Models\TeamInvitation;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

final class DeclineTeamInvitationController
{
    /**
     * Decline the invitation.
     */
    public function __invoke(RespondToTeamInvitationRequest $request, TeamInvitation $invitation, DeleteTeamInvitation $deleteTeamInvitation): RedirectResponse
    {
        $deleteTeamInvitation->handle($invitation);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Invitation declined.')]);

        return to_route('dashboard');
    }
}
