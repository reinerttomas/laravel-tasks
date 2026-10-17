<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\TaskFilters;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

final readonly class FilterTasks
{
    /**
     * Get a copy of the query narrowed to the tasks matching the search and the selected facet values.
     *
     * @param  Builder<Task>  $tasks
     * @param  'status'|'priority'|'assignee'|'project'|null  $exceptFacet  a facet to leave out, for counting its values
     * @return Builder<Task>
     */
    public function handle(Builder $tasks, TaskFilters $filters, ?string $exceptFacet = null): Builder
    {
        return $tasks->clone()
            ->when($filters->search !== '', fn (Builder $query) => $this->search($query, $filters->search))
            ->when($filters->statuses !== [] && $exceptFacet !== 'status', fn (Builder $query) => $query->whereIn(
                'tasks.status',
                array_map(fn (TaskStatus $status): string => $status->value, $filters->statuses),
            ))
            ->when($filters->priorities !== [] && $exceptFacet !== 'priority', fn (Builder $query) => $query->whereIn(
                'tasks.priority',
                array_map(fn (TaskPriority $priority): string => $priority->value, $filters->priorities),
            ))
            ->when($filters->assignees !== [] && $exceptFacet !== 'assignee', fn (Builder $query) => $query->where(
                fn (Builder $query) => $query
                    ->whereIn('tasks.assignee_id', array_values(array_diff($filters->assignees, [TaskFilters::Unassigned])))
                    ->when(in_array(TaskFilters::Unassigned, $filters->assignees, true), fn (Builder $query) => $query->orWhereNull('tasks.assignee_id')),
            ))
            ->when($filters->projects !== [] && $exceptFacet !== 'project', fn (Builder $query) => $query->whereIn('tasks.project_id', $filters->projects));
    }

    /**
     * Match the title, or the exact task reference such as WEB-42.
     *
     * @param  Builder<Task>  $query
     * @return Builder<Task>
     */
    private function search(Builder $query, string $search): Builder
    {
        $reference = preg_match('/^([A-Za-z]{2,5})-(\d+)$/', $search, $matches) === 1
            ? ['identifier' => Str::upper($matches[1]), 'number' => (int) $matches[2]]
            : null;

        // whereLike() cannot set an ESCAPE character, so the user's % and _ would act as wildcards.
        return $query->where(fn (Builder $query) => $query
            ->whereRaw("lower(tasks.title) like ? escape '!'", ['%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], Str::lower($search)).'%'])
            ->when($reference, fn (Builder $query, array $reference) => $query->orWhere(fn (Builder $query) => $query
                ->where('tasks.number', $reference['number'])
                ->whereHas('project', fn (Builder $query) => $query->where('identifier', $reference['identifier'])),
            )),
        );
    }
}
