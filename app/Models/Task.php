<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Policies\TaskPolicy;
use Carbon\CarbonImmutable;
use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read string $id
 * @property-read string $project_id
 * @property-read int $number
 * @property-read string $title
 * @property-read string|null $description
 * @property-read TaskStatus $status
 * @property-read TaskPriority $priority
 * @property-read string|null $assignee_id
 * @property-read string|null $author_id
 * @property-read CarbonImmutable|null $due_date
 * @property-read CarbonImmutable|null $created_at
 * @property-read CarbonImmutable|null $updated_at
 * @property-read string $reference
 * @property-read Project $project
 * @property-read User|null $assignee
 * @property-read User|null $author
 */
#[Fillable(['number', 'title', 'description', 'status', 'priority', 'assignee_id', 'author_id', 'due_date'])]
#[UseFactory(TaskFactory::class)]
#[UsePolicy(TaskPolicy::class)]
final class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    use HasFactory, HasUuids;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'number' => 'integer',
            'status' => TaskStatus::class,
            'priority' => TaskPriority::class,
            'due_date' => 'date',
        ];
    }

    /**
     * Get the project the task belongs to.
     *
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the member responsible for the task.
     *
     * @return BelongsTo<User, $this>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    /**
     * Get the user who created the task; null once their account is deleted.
     *
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Get the task reference, e.g. WEB-42; computed so renaming the project identifier renumbers every reference.
     *
     * @return Attribute<string, never>
     */
    protected function reference(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->project->identifier.'-'.$this->number,
        );
    }
}
