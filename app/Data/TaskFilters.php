<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Illuminate\Support\Str;

final readonly class TaskFilters
{
    /** Assignee filter value that matches tasks without an assignee. */
    public const string Unassigned = 'unassigned';

    public const array SORTABLE = ['number', 'title', 'status', 'priority', 'due_date', 'updated_at'];

    /**
     * @param  list<TaskStatus>  $statuses
     * @param  list<TaskPriority>  $priorities
     * @param  list<string>  $assignees  user ids, or {@see self::Unassigned}
     * @param  list<string>  $projects  project ids
     * @param  string  $sort  a column of {@see self::SORTABLE}, prefixed with "-" for descending
     */
    public function __construct(
        public string $search = '',
        public array $statuses = [],
        public array $priorities = [],
        public array $assignees = [],
        public array $projects = [],
        public string $sort = '-number',
    ) {
        //
    }

    /**
     * Get the sorted column.
     */
    public function sortColumn(): string
    {
        return Str::chopStart($this->sort, '-');
    }

    /**
     * Determine whether the sort is descending.
     */
    public function sortsDescending(): bool
    {
        return Str::startsWith($this->sort, '-');
    }

    /**
     * Get the filters as page props, in the shape of the query string.
     *
     * @return array{search: string, status: list<string>, priority: list<string>, assignee: list<string>, project: list<string>, sort: string}
     */
    public function toProps(): array
    {
        return [
            'search' => $this->search,
            'status' => array_map(fn (TaskStatus $status): string => $status->value, $this->statuses),
            'priority' => array_map(fn (TaskPriority $priority): string => $priority->value, $this->priorities),
            'assignee' => $this->assignees,
            'project' => $this->projects,
            'sort' => $this->sort,
        ];
    }
}
