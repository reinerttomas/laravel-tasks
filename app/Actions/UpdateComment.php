<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Comment;

final readonly class UpdateComment
{
    /**
     * Replace the comment's body.
     */
    public function handle(Comment $comment, string $body): Comment
    {
        $comment->update(['body' => $body]);

        return $comment;
    }
}
