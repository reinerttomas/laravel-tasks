<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Eloquent\Collection;

final readonly class CountTasksByStatus
{
    /**
     * Count each project's tasks per status in one grouped query, with zeros for statuses without tasks.
     *
     * @param  Collection<int, Project>  $projects
     * @return array<string, array<string, int>> counts keyed by project id, then status value
     */
    public function handle(Collection $projects): array
    {
        $none = collect(TaskStatus::cases())->mapWithKeys(fn (TaskStatus $status): array => [$status->value => 0])->all();
        $counts = $projects->mapWithKeys(fn (Project $project): array => [$project->id => $none])->all();

        $rows = Task::query()
            ->toBase()
            ->whereIn('project_id', $projects->modelKeys())
            ->select(['project_id', 'status'])
            ->selectRaw('count(*) as aggregate')
            ->groupBy(['project_id', 'status'])
            ->get();

        foreach ($rows as $row) {
            /** @var object{project_id: string, status: string, aggregate: int|string} $row */
            $counts[$row->project_id][$row->status] = (int) $row->aggregate;
        }

        return $counts;
    }
}
