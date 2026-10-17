<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Project;

final readonly class RestoreProject
{
    /**
     * Return an archived project to the active list.
     */
    public function handle(Project $project): Project
    {
        $project->update(['archived_at' => null]);

        return $project;
    }
}
