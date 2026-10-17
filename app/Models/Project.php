<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ProjectColor;
use App\Policies\ProjectPolicy;
use Carbon\CarbonImmutable;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read string $id
 * @property-read string $team_id
 * @property-read string $name
 * @property-read string $identifier
 * @property-read ProjectColor $color
 * @property-read string|null $description
 * @property-read CarbonImmutable|null $archived_at
 * @property-read bool $is_archived
 * @property-read int $next_task_number
 * @property-read CarbonImmutable|null $created_at
 * @property-read CarbonImmutable|null $updated_at
 * @property-read Team $team
 * @property-read Collection<int, Task> $tasks
 */
#[Fillable(['name', 'identifier', 'color', 'description', 'archived_at'])]
#[UseFactory(ProjectFactory::class)]
#[UsePolicy(ProjectPolicy::class)]
final class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory, HasUuids;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'color' => ProjectColor::class,
            'archived_at' => 'datetime',
            'next_task_number' => 'integer',
        ];
    }

    /**
     * Get the team that owns the project.
     *
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the tasks of the project.
     *
     * @return HasMany<Task, $this>
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Limit the query to projects that are not archived.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->whereNull('archived_at');
    }

    /**
     * Limit the query to archived projects.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function archived(Builder $query): void
    {
        $query->whereNotNull('archived_at');
    }

    /**
     * Determine whether the project is archived, which makes it and its tasks read-only.
     *
     * @return Attribute<bool, never>
     */
    protected function isArchived(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => $this->archived_at !== null,
        );
    }
}
