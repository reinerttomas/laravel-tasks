<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\TaskFilters;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final readonly class ListTasks
{
    public const int PerPage = 25;

    public function __construct(private FilterTasks $filterTasks) {}

    /**
     * Get one page of the filtered and sorted tasks, with their project and assignee loaded.
     *
     * @param  Builder<Task>  $tasks
     * @return LengthAwarePaginator<int, Task>
     */
    public function handle(Builder $tasks, TaskFilters $filters, int $page = 1): LengthAwarePaginator
    {
        $query = $this->filterTasks->handle($tasks, $filters)
            ->select('tasks.*')
            ->with(['project', 'assignee']);

        $this->sort($query, $filters);

        return $query
            ->paginate(perPage: self::PerPage, page: $page)
            ->withQueryString();
    }

    /**
     * Order by the requested column, then newest first.
     *
     * @param  Builder<Task>  $query
     */
    private function sort(Builder $query, TaskFilters $filters): void
    {
        $direction = $filters->sortsDescending() ? 'desc' : 'asc';

        // Ranks follow the enum order (ADR 0004 fixes the statuses): status by workflow, priority by urgency.
        match ($filters->sortColumn()) {
            'status' => $query->orderByRaw(
                'case tasks.status when ? then 0 when ? then 1 when ? then 2 when ? then 3 when ? then 4 end '.$direction,
                array_map(fn (TaskStatus $status): string => $status->value, TaskStatus::cases()),
            ),
            'priority' => $query->orderByRaw(
                'case tasks.priority when ? then 0 when ? then 1 when ? then 2 when ? then 3 when ? then 4 end '.$direction,
                array_map(fn (TaskPriority $priority): string => $priority->value, TaskPriority::byUrgency()),
            ),
            'due_date' => $query->orderByRaw('tasks.due_date is null')->orderBy('tasks.due_date', $direction),
            default => $query->orderBy('tasks.'.$filters->sortColumn(), $direction),
        };

        // UUIDv7 ids follow creation order, a stable tiebreaker even across projects.
        $query->orderByDesc('tasks.id');
    }
}
