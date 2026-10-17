<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Comment;

final readonly class DeleteComment
{
    /**
     * Permanently delete the comment.
     */
    public function handle(Comment $comment): void
    {
        $comment->delete();
    }
}
