<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ProjectColor;
use App\Models\Project;

final readonly class UpdateProject
{
    /**
     * Update the project's details; a new identifier changes every task reference.
     */
    public function handle(Project $project, string $name, string $identifier, ProjectColor $color, ?string $description): Project
    {
        $project->update([
            'name' => $name,
            'identifier' => $identifier,
            'color' => $color,
            'description' => $description,
        ]);

        return $project;
    }
}
