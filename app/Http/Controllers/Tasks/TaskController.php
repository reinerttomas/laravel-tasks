<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tasks;

use App\Actions\CreateTask;
use App\Actions\DeleteTask;
use App\Actions\UpdateTask;
use App\Data\CommentItem;
use App\Data\TaskDetail;
use App\Http\Requests\Tasks\SaveTaskRequest;
use App\Models\Comment;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        $user = $request->user();

        $task->load([
            'project',
            'assignee',
            'author',
            // Chaperoning hands each comment its task, so the Policy checks below do not reload it per comment.
            'comments' => fn (HasMany $query) => $query->with('author')->oldest()->chaperone(),
        ]);

        return Inertia::render('tasks/show', [
            'task' => TaskDetail::fromTask($task),
            'comments' => $task->comments->map(fn (Comment $comment): CommentItem => CommentItem::fromComment(
                $comment,
                canUpdate: $user?->can('update', $comment) ?? false,
                canDelete: $user?->can('delete', $comment) ?? false,
            )),
            'members' => $currentTeam->members()
                ->orderBy('name')
                ->get()
                ->map(fn (User $member): array => ['id' => $member->id, 'name' => $member->name]),
            'can' => [
                'update' => $user?->can('update', $task) ?? false,
                'delete' => $user?->can('delete', $task) ?? false,
                'createComment' => $user?->can('create', [Comment::class, $task]) ?? false,
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
