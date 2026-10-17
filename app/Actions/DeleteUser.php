<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final readonly class DeleteUser
{
    /**
     * Delete the user with their memberships, passkeys and sent invitations; their teams remain.
     */
    public function handle(User $user): void
    {
        DB::transaction(function () use ($user): void {
            TeamInvitation::query()->where('invited_by', $user->id)->delete();
            $user->teamMemberships()->delete();
            $user->passkeys()->delete();
            $user->delete();
        });
    }
}
