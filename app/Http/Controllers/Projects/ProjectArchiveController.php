<?php

declare(strict_types=1);

namespace App\Http\Controllers\Projects;

use App\Actions\ArchiveProject;
use App\Actions\RestoreProject;
use App\Models\Project;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

final class ProjectArchiveController
{
    /**
     * Archive the specified project.
     */
    public function store(Team $currentTeam, Project $project, ArchiveProject $archiveProject): RedirectResponse
    {
        Gate::authorize('archive', $project);

        $archiveProject->handle($project);

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name archived.', ['name' => $project->name])]);

        return back();
    }

    /**
     * Restore the specified archived project.
     */
    public function destroy(Team $currentTeam, Project $project, RestoreProject $restoreProject): RedirectResponse
    {
        Gate::authorize('restore', $project);

        $restoreProject->handle($project);

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name restored.', ['name' => $project->name])]);

        return back();
    }
}
