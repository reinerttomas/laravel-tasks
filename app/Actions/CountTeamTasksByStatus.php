<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\Team;

final readonly class CountTeamTasksByStatus
{
    /**
     * Count the tasks of the team's active projects per status, with zeros for statuses without tasks.
     *
     * @return array<string, int> counts keyed by status value
     */
    public function handle(Team $team): array
    {
        $counts = collect(TaskStatus::cases())->mapWithKeys(fn (TaskStatus $status): array => [$status->value => 0])->all();

        $rows = Task::query()
            ->toBase()
            ->whereIn('project_id', $team->projects()->active()->select('id'))
            ->select('status')
            ->selectRaw('count(*) as aggregate')
            ->groupBy('status')
            ->get();

        foreach ($rows as $row) {
            /** @var object{status: string, aggregate: int|string} $row */
            $counts[$row->status] = (int) $row->aggregate;
        }

        return $counts;
    }
}
