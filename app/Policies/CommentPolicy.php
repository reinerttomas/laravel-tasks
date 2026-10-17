<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\TeamPermission;
use App\Models\Comment;
use App\Models\Task;
use App\Models\User;

final class CommentPolicy
{
    /**
     * Determine whether the user can comment on the task; archived projects are read-only.
     */
    public function create(User $user, Task $task): bool
    {
        return ! $task->project->is_archived
            && $user->hasTeamPermission($task->project->team, TeamPermission::CreateComment);
    }

    /**
     * Determine whether the user can edit the comment; only its author may, and only while they can still comment.
     */
    public function update(User $user, Comment $comment): bool
    {
        return $comment->author_id === $user->id && $this->create($user, $comment->task);
    }

    /**
     * Determine whether the user can delete the comment; without the delete permission, authors may still delete their own.
     */
    public function delete(User $user, Comment $comment): bool
    {
        $project = $comment->task->project;

        if ($project->is_archived) {
            return false;
        }

        return $user->hasTeamPermission($project->team, TeamPermission::DeleteComment)
            || $this->update($user, $comment);
    }
}
