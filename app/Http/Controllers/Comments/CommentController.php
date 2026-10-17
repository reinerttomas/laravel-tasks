<?php

declare(strict_types=1);

namespace App\Http\Controllers\Comments;

use App\Actions\CreateComment;
use App\Actions\DeleteComment;
use App\Actions\UpdateComment;
use App\Http\Requests\Comments\SaveCommentRequest;
use App\Models\Comment;
use App\Models\Task;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

final class CommentController
{
    /**
     * Add a comment to the task.
     */
    public function store(SaveCommentRequest $request, Team $currentTeam, Task $task, CreateComment $createComment): RedirectResponse
    {
        Gate::authorize('create', [Comment::class, $task]);

        $createComment->handle($task, $request->user(), $request->string('body')->value());

        return back();
    }

    /**
     * Replace the comment's body.
     */
    public function update(SaveCommentRequest $request, Team $currentTeam, Task $task, Comment $comment, UpdateComment $updateComment): RedirectResponse
    {
        Gate::authorize('update', $comment);

        $updateComment->handle($comment, $request->string('body')->value());

        return back();
    }

    /**
     * Delete the comment.
     */
    public function destroy(Team $currentTeam, Task $task, Comment $comment, DeleteComment $deleteComment): RedirectResponse
    {
        Gate::authorize('delete', $comment);

        $deleteComment->handle($comment);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Comment deleted.')]);

        return back();
    }
}
