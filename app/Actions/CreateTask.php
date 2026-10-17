<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final readonly class CreateTask
{
    /**
     * Create a task in the project, numbered with the project's next task number.
     *
     * @param  array{title?: string, description?: string|null, status?: TaskStatus, priority?: TaskPriority, assignee_id?: string|null, due_date?: CarbonImmutable|null}  $attributes
     */
    public function handle(Project $project, User $author, string $title, array $attributes = []): Task
    {
        return DB::transaction(function () use ($project, $author, $title, $attributes): Task {
            // Locking the project row serialises concurrent creates, so two tasks never take the same number.
            $number = Project::query()->lockForUpdate()->findOrFail($project->id)->next_task_number;

            $project->newQuery()->whereKey($project->id)->increment('next_task_number');

            return $project->tasks()->create([
                'status' => TaskStatus::Backlog,
                'priority' => TaskPriority::None,
                ...$attributes,
                'title' => $title,
                'number' => $number,
                'author_id' => $author->id,
            ]);
        });
    }
}
