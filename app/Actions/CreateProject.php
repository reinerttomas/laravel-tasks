<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ProjectColor;
use App\Models\Project;
use App\Models\Team;

final readonly class CreateProject
{
    /**
     * Create a project in the team.
     */
    public function handle(Team $team, string $name, string $identifier, ProjectColor $color, ?string $description): Project
    {
        return $team->projects()->create([
            'name' => $name,
            'identifier' => $identifier,
            'color' => $color,
            'description' => $description,
        ]);
    }
}
