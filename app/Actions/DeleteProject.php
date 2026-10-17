<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Comment;
use App\Models\Project;
use Illuminate\Support\Facades\DB;

final readonly class DeleteProject
{
    /**
     * Permanently delete the project with its tasks and their comments.
     */
    public function handle(Project $project): void
    {
        DB::transaction(function () use ($project): void {
            Comment::query()->whereRelation('task', 'project_id', $project->id)->delete();
            $project->tasks()->delete();
            $project->delete();
        });
    }
}
