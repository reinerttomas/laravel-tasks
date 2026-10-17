<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\TaskFilters;
use App\Models\Task;
use Illuminate\Database\Eloquent\Builder;

final readonly class CountTaskFacets
{
    private const array COLUMNS = [
        'status' => 'tasks.status',
        'priority' => 'tasks.priority',
        'assignee' => 'tasks.assignee_id',
        'project' => 'tasks.project_id',
    ];

    public function __construct(private FilterTasks $filterTasks) {}

    /**
     * Count the tasks per value of each facet, applying every filter except the facet's own, so each count shows what selecting the value adds.
     *
     * @param  Builder<Task>  $tasks
     * @param  list<'status'|'priority'|'assignee'|'project'>  $facets
     * @return array<string, array<string, int>> counts keyed by facet, then value; tasks without an assignee count under {@see TaskFilters::Unassigned}
     */
    public function handle(Builder $tasks, TaskFilters $filters, array $facets): array
    {
        $counts = [];

        foreach ($facets as $facet) {
            $column = self::COLUMNS[$facet];

            $rows = $this->filterTasks->handle($tasks, $filters, exceptFacet: $facet)
                ->toBase()
                ->select($column.' as value')
                ->selectRaw('count(*) as aggregate')
                ->groupBy($column)
                ->get();

            $counts[$facet] = [];

            foreach ($rows as $row) {
                /** @var object{value: string|null, aggregate: int|string} $row */
                $counts[$facet][$row->value ?? TaskFilters::Unassigned] = (int) $row->aggregate;
            }
        }

        return $counts;
    }
}
