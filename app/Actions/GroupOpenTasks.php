<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final readonly class GroupOpenTasks
{
    /**
     * Group the open tasks assigned to the user in the team's active projects by what needs attention first, soonest due first.
     *
     * Overdue tasks of any open status come first; of the rest, only started and to-do tasks are listed, backlog waits.
     *
     * @return array{overdue: Collection<int, Task>, inProgress: Collection<int, Task>, upNext: Collection<int, Task>}
     */
    public function handle(Team $team, User $assignee): array
    {
        $today = today();

        $tasks = Task::query()
            ->whereBelongsTo($assignee, 'assignee')
            ->whereHas('project', fn (Builder $query) => $query->whereBelongsTo($team)->active())
            ->whereIn('status', TaskStatus::open())
            ->where(fn (Builder $query) => $query
                ->whereIn('status', [TaskStatus::Todo, TaskStatus::InProgress])
                ->orWhere('due_date', '<', $today->toDateString()))
            ->with(['project', 'assignee'])
            ->orderByRaw('due_date is null')
            ->orderBy('due_date')
            ->orderByDesc('id')
            ->get();

        [$overdue, $onTime] = $tasks->partition(fn (Task $task): bool => $task->due_date?->lt($today) ?? false);

        return [
            'overdue' => $overdue->values(),
            'inProgress' => $onTime->where('status', TaskStatus::InProgress)->values(),
            'upNext' => $onTime->where('status', TaskStatus::Todo)->values(),
        ];
    }
}
