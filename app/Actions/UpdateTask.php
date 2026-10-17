<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use Carbon\CarbonImmutable;

final readonly class UpdateTask
{
    /**
     * Update the given fields of the task, leaving the others untouched.
     *
     * @param  array{title?: string, description?: string|null, status?: TaskStatus, priority?: TaskPriority, assignee_id?: string|null, due_date?: CarbonImmutable|null}  $attributes
     */
    public function handle(Task $task, array $attributes): Task
    {
        $task->update($attributes);

        return $task;
    }
}
