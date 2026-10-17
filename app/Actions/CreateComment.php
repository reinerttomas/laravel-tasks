<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Comment;
use App\Models\Task;
use App\Models\User;

final readonly class CreateComment
{
    /**
     * Add a comment by the author to the task.
     */
    public function handle(Task $task, User $author, string $body): Comment
    {
        return $task->comments()->create([
            'body' => $body,
            'author_id' => $author->id,
        ]);
    }
}
