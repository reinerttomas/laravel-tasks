<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\Task;

/**
 * A task as a row of the task table; expects the task's project and assignee to be loaded.
 */
final readonly class TaskRow
{
    /**
     * @param  array{id: string, identifier: string, color: string}  $project
     * @param  array{id: string, name: string}|null  $assignee
     */
    public function __construct(
        public string $id,
        public int $number,
        public string $reference,
        public string $title,
        public string $status,
        public string $priority,
        public array $project,
        public ?array $assignee,
        public ?string $dueDate,
        public ?string $updatedAt,
    ) {
        //
    }

    /**
     * Create the row from the task.
     */
    public static function fromTask(Task $task): self
    {
        return new self(
            id: $task->id,
            number: $task->number,
            reference: $task->reference,
            title: $task->title,
            status: $task->status->value,
            priority: $task->priority->value,
            project: [
                'id' => $task->project->id,
                'identifier' => $task->project->identifier,
                'color' => $task->project->color->value,
            ],
            assignee: $task->assignee ? ['id' => $task->assignee->id, 'name' => $task->assignee->name] : null,
            dueDate: $task->due_date?->toDateString(),
            updatedAt: $task->updated_at?->toISOString(),
        );
    }
}
