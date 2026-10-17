<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tasks;

use App\Actions\CreateTask;
use App\Actions\DeleteTask;
use App\Actions\UpdateTask;
use App\Data\TaskDetail;
use App\Http\Requests\Tasks\SaveTaskRequest;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final class TaskController
{
    /**
     * Display the task with what the viewer may change on it.
     */
    public function show(Request $request, Team $currentTeam, Task $task): Response
    {
        $task->load(['project', 'assignee', 'author']);

        return Inertia::render('tasks/show', [
            'task' => TaskDetail::fromTask($task),
            'members' => $currentTeam->members()
                ->orderBy('name')
                ->get()
                ->map(fn (User $member): array => ['id' => $member->id, 'name' => $member->name]),
            'can' => [
                'update' => $request->user()?->can('update', $task) ?? false,
                'delete' => $request->user()?->can('delete', $task) ?? false,
            ],
        ]);
    }

    /**
     * Store a newly created task in the project.
     */
    public function store(SaveTaskRequest $request, Team $currentTeam, Project $project, CreateTask $createTask): RedirectResponse
    {
        Gate::authorize('create', [Task::class, $project]);

        $task = $createTask->handle($project, $request->user(), $request->string('title')->value(), $request->taskAttributes());

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':reference created.', ['reference' => $task->reference])]);

        return back();
    }

    /**
     * Update the given fields of the task.
     */
    public function update(SaveTaskRequest $request, Team $currentTeam, Task $task, UpdateTask $updateTask): RedirectResponse
    {
        Gate::authorize('update', $task);

        $updateTask->handle($task, $request->taskAttributes());

        return back();
    }

    /**
     * Delete the task and return to its project.
     */
    public function destroy(Team $currentTeam, Task $task, DeleteTask $deleteTask): RedirectResponse
    {
        Gate::authorize('delete', $task);

        $deleteTask->handle($task);

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':reference deleted.', ['reference' => $task->reference])]);

        return to_route('projects.show', ['current_team' => $currentTeam, 'project' => $task->project_id]);
    }
}
