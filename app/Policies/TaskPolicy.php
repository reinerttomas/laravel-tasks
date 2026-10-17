<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\TeamPermission;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;

final class TaskPolicy
{
    /**
     * Determine whether the user can create tasks in the project; archived projects are read-only.
     */
    public function create(User $user, Project $project): bool
    {
        return ! $project->is_archived
            && $user->hasTeamPermission($project->team, TeamPermission::CreateTask);
    }

    /**
     * Determine whether the user can update the task.
     */
    public function update(User $user, Task $task): bool
    {
        return ! $task->project->is_archived
            && $user->hasTeamPermission($task->project->team, TeamPermission::UpdateTask);
    }

    /**
     * Determine whether the user can delete the task; without the delete permission, authors may still delete their own.
     */
    public function delete(User $user, Task $task): bool
    {
        if ($task->project->is_archived) {
            return false;
        }

        return $user->hasTeamPermission($task->project->team, TeamPermission::DeleteTask)
            || ($task->author_id === $user->id && $this->create($user, $task->project));
    }
}
