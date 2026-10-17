<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Project;

final readonly class ArchiveProject
{
    /**
     * Archive the project, hiding it from the active list and making it read-only.
     */
    public function handle(Project $project): Project
    {
        $project->update(['archived_at' => now()]);

        return $project;
    }
}
