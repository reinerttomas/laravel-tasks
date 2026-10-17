<?php

declare(strict_types=1);

namespace App\Http\Controllers\Teams;

use App\Actions\AcceptTeamInvitation;
use App\Http\Requests\Teams\RespondToTeamInvitationRequest;
use App\Models\TeamInvitation;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

final class AcceptTeamInvitationController
{
    /**
     * Accept the invitation.
     */
    public function __invoke(RespondToTeamInvitationRequest $request, TeamInvitation $invitation, AcceptTeamInvitation $acceptTeamInvitation): RedirectResponse
    {
        $acceptTeamInvitation->handle($request->user(), $invitation);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Invitation accepted.')]);

        return to_route('dashboard');
    }
}
