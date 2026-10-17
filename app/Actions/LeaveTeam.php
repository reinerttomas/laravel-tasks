<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final readonly class LeaveTeam
{
    public function __construct(private SwitchTeam $switchTeam) {}

    /**
     * Remove the user's own membership of the team, unassigning their tasks in it; they stay the author.
     */
    public function handle(User $user, Team $team): void
    {
        $fallbackTeam = $user->isCurrentTeam($team)
            ? $user->fallbackTeam($team)
            : null;

        DB::transaction(function () use ($user, $team): void {
            Task::query()
                ->whereRelation('project', 'team_id', $team->id)
                ->where('assignee_id', $user->id)
                ->update(['assignee_id' => null]);

            $team->memberships()
                ->where('user_id', $user->id)
                ->delete();
        });

        if ($fallbackTeam) {
            $this->switchTeam->handle($user, $fallbackTeam);
        }
    }
}
