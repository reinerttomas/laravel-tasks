<?php

declare(strict_types=1);

namespace App\Models;

use App\Policies\CommentPolicy;
use Carbon\CarbonImmutable;
use Database\Factories\CommentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read string $id
 * @property-read string $task_id
 * @property-read string|null $author_id
 * @property-read string $body
 * @property-read CarbonImmutable|null $created_at
 * @property-read CarbonImmutable|null $updated_at
 * @property-read Task $task
 * @property-read User|null $author
 */
#[Fillable(['body', 'author_id'])]
#[UseFactory(CommentFactory::class)]
#[UsePolicy(CommentPolicy::class)]
final class Comment extends Model
{
    /** @use HasFactory<CommentFactory> */
    use HasFactory, HasUuids;

    /**
     * Get the task the comment is on.
     *
     * @return BelongsTo<Task, $this>
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * Get the user who wrote the comment; null once their account is deleted.
     *
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
