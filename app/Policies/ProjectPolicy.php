<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\TeamPermission;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;

final class ProjectPolicy
{
    /**
     * Determine whether the user can create projects in the team.
     */
    public function create(User $user, Team $team): bool
    {
        return $user->hasTeamPermission($team, TeamPermission::CreateProject);
    }

    /**
     * Determine whether the user can update the project; archived projects are read-only.
     */
    public function update(User $user, Project $project): bool
    {
        return ! $project->is_archived
            && $user->hasTeamPermission($project->team, TeamPermission::UpdateProject);
    }

    /**
     * Determine whether the user can archive the project.
     */
    public function archive(User $user, Project $project): bool
    {
        return ! $project->is_archived
            && $user->hasTeamPermission($project->team, TeamPermission::ArchiveProject);
    }

    /**
     * Determine whether the user can restore the archived project.
     */
    public function restore(User $user, Project $project): bool
    {
        return $project->is_archived
            && $user->hasTeamPermission($project->team, TeamPermission::ArchiveProject);
    }

    /**
     * Determine whether the user can delete the project.
     */
    public function delete(User $user, Project $project): bool
    {
        return $user->hasTeamPermission($project->team, TeamPermission::DeleteProject);
    }
}
