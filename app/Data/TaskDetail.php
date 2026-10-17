<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\Task;

/**
 * A task as shown on its detail page; expects the task's project, assignee and author to be loaded.
 */
final readonly class TaskDetail
{
    /**
     * @param  array{id: string, name: string, identifier: string, color: string, archivedAt: string|null}  $project
     * @param  array{id: string, name: string}|null  $assignee
     * @param  array{id: string, name: string}|null  $author
     */
    public function __construct(
        public string $id,
        public string $reference,
        public string $title,
        public ?string $description,
        public string $status,
        public string $priority,
        public array $project,
        public ?array $assignee,
        public ?array $author,
        public ?string $dueDate,
        public ?string $createdAt,
        public ?string $updatedAt,
    ) {
        //
    }

    /**
     * Create the detail from the task.
     */
    public static function fromTask(Task $task): self
    {
        return new self(
            id: $task->id,
            reference: $task->reference,
            title: $task->title,
            description: $task->description,
            status: $task->status->value,
            priority: $task->priority->value,
            project: [
                'id' => $task->project->id,
                'name' => $task->project->name,
                'identifier' => $task->project->identifier,
                'color' => $task->project->color->value,
                'archivedAt' => $task->project->archived_at?->toISOString(),
            ],
            assignee: $task->assignee ? ['id' => $task->assignee->id, 'name' => $task->assignee->name] : null,
            author: $task->author ? ['id' => $task->author->id, 'name' => $task->author->name] : null,
            dueDate: $task->due_date?->toDateString(),
            createdAt: $task->created_at?->toISOString(),
            updatedAt: $task->updated_at?->toISOString(),
        );
    }
}
