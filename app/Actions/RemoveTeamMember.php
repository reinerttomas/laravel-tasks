<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final readonly class RemoveTeamMember
{
    public function __construct(private SwitchTeam $switchTeam) {}

    /**
     * Remove the member from the team, sending them back to their personal team.
     *
     * Their tasks in the team become unassigned; they stay the author.
     */
    public function handle(Team $team, User $member): void
    {
        DB::transaction(function () use ($team, $member): void {
            Task::query()
                ->whereRelation('project', 'team_id', $team->id)
                ->where('assignee_id', $member->id)
                ->update(['assignee_id' => null]);

            $team->memberships()
                ->where('user_id', $member->id)
                ->delete();
        });

        if ($member->isCurrentTeam($team)) {
            $this->switchTeam->handle($member, $member->personalTeam());
        }
    }
}
