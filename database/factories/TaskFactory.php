<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Task>
 */
final class TaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'number' => fn (array $attributes): int => $this->nextNumber($attributes['project_id']),
            'title' => Str::chopEnd(fake()->sentence(4), '.'),
            'description' => fake()->paragraph(),
            'status' => TaskStatus::Backlog,
            'priority' => TaskPriority::None,
            'author_id' => User::factory(),
        ];
    }

    /**
     * Take the project's next task number the way CreateTask does, so factory tasks never collide.
     */
    private function nextNumber(string $projectId): int
    {
        $project = Project::query()->findOrFail($projectId);
        $number = $project->next_task_number;
        $project->increment('next_task_number');

        return $number;
    }
}
