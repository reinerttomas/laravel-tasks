<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\Comment;

/**
 * A comment in a task's thread with what the viewer may do to it; expects the comment's author to be loaded.
 */
final readonly class CommentItem
{
    /**
     * @param  array{id: string, name: string}|null  $author
     * @param  array{update: bool, delete: bool}  $can
     */
    public function __construct(
        public string $id,
        public string $body,
        public ?array $author,
        public ?string $createdAt,
        public ?string $updatedAt,
        public array $can,
    ) {
        //
    }

    /**
     * Create the item from the comment and the viewer's abilities on it.
     */
    public static function fromComment(Comment $comment, bool $canUpdate, bool $canDelete): self
    {
        return new self(
            id: $comment->id,
            body: $comment->body,
            author: $comment->author ? ['id' => $comment->author->id, 'name' => $comment->author->name] : null,
            createdAt: $comment->created_at?->toISOString(),
            updatedAt: $comment->updated_at?->toISOString(),
            can: ['update' => $canUpdate, 'delete' => $canDelete],
        );
    }
}
