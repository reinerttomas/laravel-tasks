<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Task;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final readonly class DeleteUser
{
    /**
     * Delete the user with their memberships, passkeys and sent invitations; their teams remain.
     *
     * The tasks they wrote or were assigned stay with the team, unassigned and without an author.
     */
    public function handle(User $user): void
    {
        DB::transaction(function () use ($user): void {
            TeamInvitation::query()->where('invited_by', $user->id)->delete();
            Task::query()->where('assignee_id', $user->id)->update(['assignee_id' => null]);
            Task::query()->where('author_id', $user->id)->update(['author_id' => null]);
            $user->teamMemberships()->delete();
            $user->passkeys()->delete();
            $user->delete();
        });
    }
}
