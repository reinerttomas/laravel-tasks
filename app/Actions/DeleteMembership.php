<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final readonly class DeleteMembership
{
    /**
     * Take the user out of the team, unassigning their tasks in it; they stay the author.
     */
    public function handle(Team $team, User $user): void
    {
        DB::transaction(function () use ($team, $user): void {
            Task::query()
                ->whereRelation('project', 'team_id', $team->id)
                ->where('assignee_id', $user->id)
                ->update(['assignee_id' => null]);

            $team->memberships()
                ->where('user_id', $user->id)
                ->delete();
        });
    }
}
