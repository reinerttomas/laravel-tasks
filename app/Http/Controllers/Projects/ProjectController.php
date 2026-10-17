<?php

declare(strict_types=1);

namespace App\Http\Controllers\Projects;

use App\Actions\CountTasksByStatus;
use App\Actions\CreateProject;
use App\Actions\DeleteProject;
use App\Actions\UpdateProject;
use App\Enums\ProjectColor;
use App\Http\Requests\Projects\DeleteProjectRequest;
use App\Http\Requests\Projects\SaveProjectRequest;
use App\Models\Project;
use App\Models\Team;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final class ProjectController
{
    /**
     * Display the team's active or archived projects.
     */
    public function index(Request $request, Team $currentTeam, CountTasksByStatus $countTasksByStatus): Response
    {
        $filter = $request->query('filter') === 'archived' ? 'archived' : 'active';

        $projects = $currentTeam->projects()
            ->when($filter === 'archived', fn (Builder $query): Builder => $query->archived(), fn (Builder $query): Builder => $query->active())
            ->orderBy('name')
            ->get();

        $taskCounts = $countTasksByStatus->handle($projects);

        return Inertia::render('projects/index', [
            'filter' => $filter,
            'projects' => $projects->map(fn (Project $project): array => [
                ...$this->toProps($project),
                'taskCounts' => $taskCounts[$project->id],
            ]),
            'suggestedColor' => ProjectColor::firstUnused($currentTeam->projects()->active()->pluck('color')),
        ]);
    }

    /**
     * Display the project.
     */
    public function show(Team $currentTeam, Project $project): Response
    {
        return Inertia::render('projects/show', [
            'project' => $this->toProps($project),
        ]);
    }

    /**
     * Store a newly created project.
     */
    public function store(SaveProjectRequest $request, Team $currentTeam, CreateProject $createProject): RedirectResponse
    {
        Gate::authorize('create', [Project::class, $currentTeam]);

        $project = $createProject->handle(
            $currentTeam,
            $request->string('name')->value(),
            $request->string('identifier')->value(),
            $request->enum('color', ProjectColor::class),
            $request->validated('description'),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name created.', ['name' => $project->name])]);

        return to_route('projects.index', ['current_team' => $currentTeam]);
    }

    /**
     * Update the specified project.
     */
    public function update(SaveProjectRequest $request, Team $currentTeam, Project $project, UpdateProject $updateProject): RedirectResponse
    {
        Gate::authorize('update', $project);

        $project = $updateProject->handle(
            $project,
            $request->string('name')->value(),
            $request->string('identifier')->value(),
            $request->enum('color', ProjectColor::class),
            $request->validated('description'),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name updated.', ['name' => $project->name])]);

        return back();
    }

    /**
     * Delete the specified project.
     */
    public function destroy(DeleteProjectRequest $request, Team $currentTeam, Project $project, DeleteProject $deleteProject): RedirectResponse
    {
        $deleteProject->handle($project);

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name deleted.', ['name' => $project->name])]);

        return to_route('projects.index', ['current_team' => $currentTeam]);
    }

    /**
     * Get the project's page props.
     *
     * @return array{id: string, name: string, identifier: string, color: string, description: string|null, archivedAt: string|null, updatedAt: string|null}
     */
    private function toProps(Project $project): array
    {
        return [
            'id' => $project->id,
            'name' => $project->name,
            'identifier' => $project->identifier,
            'color' => $project->color->value,
            'description' => $project->description,
            'archivedAt' => $project->archived_at?->toISOString(),
            'updatedAt' => $project->updated_at?->toISOString(),
        ];
    }
}
