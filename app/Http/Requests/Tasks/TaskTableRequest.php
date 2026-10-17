<?php

declare(strict_types=1);

namespace App\Http\Requests\Tasks;

use App\Data\TaskFilters;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

/**
 * Reads the task table state from the query string; unknown values are dropped rather than rejected, so a stale link still opens.
 */
final class TaskTableRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, never>
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * Get the table filters from the query string.
     *
     * @param  list<TaskStatus>  $defaultStatuses  used when the query string has no status at all; the table sends `status[]=` to select every status
     */
    public function filters(array $defaultStatuses = []): TaskFilters
    {
        $sort = $this->string('sort')->value();

        return new TaskFilters(
            search: Str::trim($this->string('search')->value()),
            statuses: $this->has('status')
                ? array_values(array_filter(array_map(TaskStatus::tryFrom(...), $this->strings('status'))))
                : $defaultStatuses,
            priorities: array_values(array_filter(array_map(TaskPriority::tryFrom(...), $this->strings('priority')))),
            assignees: $this->strings('assignee'),
            projects: $this->strings('project'),
            sort: in_array(Str::chopStart($sort, '-'), TaskFilters::SORTABLE, true) ? $sort : '-number',
        );
    }

    /**
     * Get the distinct string values of a query string array.
     *
     * @return list<string>
     */
    private function strings(string $key): array
    {
        $values = $this->query($key);

        return is_array($values)
            ? array_values(array_unique(array_filter($values, is_string(...))))
            : [];
    }
}
