<?php

declare(strict_types=1);

use App\Enums\TeamRole;
use App\Models\Comment;
use App\Models\Membership;
use App\Models\Project;
use App\Models\Task;

describe('store', function () {
    test('members add a comment to a task', function () {
        $membership = Membership::factory()->role(TeamRole::Member)->create();
        $task = Task::factory()->for(Project::factory()->for($membership->team))->create();

        $response = $this
            ->actingAs($membership->user)
            ->from(route('tasks.show', ['current_team' => $membership->team, 'task' => $task]))
            ->post(route('comments.store', ['current_team' => $membership->team, 'task' => $task]), [
                'body' => 'Reproduced on **Safari**.',
            ]);

        $response->assertRedirect(route('tasks.show', ['current_team' => $membership->team, 'task' => $task]));
        expect($task->comments()->sole())
            ->body->toBe('Reproduced on **Safari**.')
            ->author_id->toBe($membership->user->id);
    });

    test('the body is required', function () {
        $membership = Membership::factory()->role(TeamRole::Member)->create();
        $task = Task::factory()->for(Project::factory()->for($membership->team))->create();

        $response = $this
            ->actingAs($membership->user)
            ->post(route('comments.store', ['current_team' => $membership->team, 'task' => $task]), ['body' => '']);

        $response->assertInvalid(['body' => 'The body field is required.']);
        expect($task->comments()->exists())->toBeFalse();
    });

    test('viewers cannot comment', function () {
        $membership = Membership::factory()->role(TeamRole::Viewer)->create();
        $task = Task::factory()->for(Project::factory()->for($membership->team))->create();

        $response = $this
            ->actingAs($membership->user)
            ->post(route('comments.store', ['current_team' => $membership->team, 'task' => $task]), ['body' => 'Hello']);

        $response->assertForbidden();
        expect($task->comments()->exists())->toBeFalse();
    });

    test('a task in an archived project rejects new comments', function () {
        $membership = Membership::factory()->role(TeamRole::Owner)->create();
        $task = Task::factory()->for(Project::factory()->for($membership->team)->archived())->create();

        $response = $this
            ->actingAs($membership->user)
            ->post(route('comments.store', ['current_team' => $membership->team, 'task' => $task]), ['body' => 'Hello']);

        $response->assertForbidden();
        expect($task->comments()->exists())->toBeFalse();
    });

    test('a task of another team returns 404', function () {
        $membership = Membership::factory()->role(TeamRole::Owner)->create();
        $task = Task::factory()->create();

        $response = $this
            ->actingAs($membership->user)
            ->post(route('comments.store', ['current_team' => $membership->team, 'task' => $task]), ['body' => 'Hello']);

        $response->assertNotFound();
        expect($task->comments()->exists())->toBeFalse();
    });
});

describe('update', function () {
    test('authors edit their comment', function () {
        $membership = Membership::factory()->role(TeamRole::Member)->create();
        $task = Task::factory()->for(Project::factory()->for($membership->team))->create();
        $comment = Comment::factory()->for($task)->for($membership->user, 'author')->create(['body' => 'Typo']);

        $response = $this
            ->actingAs($membership->user)
            ->from(route('tasks.show', ['current_team' => $membership->team, 'task' => $task]))
            ->patch(route('comments.update', ['current_team' => $membership->team, 'task' => $task, 'comment' => $comment]), [
                'body' => 'Fixed the **typo**.',
            ]);

        $response->assertRedirect(route('tasks.show', ['current_team' => $membership->team, 'task' => $task]));
        expect($comment->fresh()->body)->toBe('Fixed the **typo**.');
    });

    test('the body cannot be emptied', function () {
        $membership = Membership::factory()->role(TeamRole::Member)->create();
        $task = Task::factory()->for(Project::factory()->for($membership->team))->create();
        $comment = Comment::factory()->for($task)->for($membership->user, 'author')->create(['body' => 'Keep me']);

        $response = $this
            ->actingAs($membership->user)
            ->patch(route('comments.update', ['current_team' => $membership->team, 'task' => $task, 'comment' => $comment]), ['body' => '']);

        $response->assertInvalid(['body' => 'The body field is required.']);
        expect($comment->fresh()->body)->toBe('Keep me');
    });

    test('an admin cannot edit someone else\'s comment', function () {
        $membership = Membership::factory()->role(TeamRole::Admin)->create();
        $task = Task::factory()->for(Project::factory()->for($membership->team))->create();
        $comment = Comment::factory()->for($task)->create(['body' => 'Keep me']);

        $response = $this
            ->actingAs($membership->user)
            ->patch(route('comments.update', ['current_team' => $membership->team, 'task' => $task, 'comment' => $comment]), ['body' => 'Changed']);

        $response->assertForbidden();
        expect($comment->fresh()->body)->toBe('Keep me');
    });

    test('a comment on another task returns 404', function () {
        $membership = Membership::factory()->role(TeamRole::Member)->create();
        $project = Project::factory()->for($membership->team)->create();
        $task = Task::factory()->for($project)->create();
        $comment = Comment::factory()->for(Task::factory()->for($project))->for($membership->user, 'author')->create(['body' => 'Keep me']);

        $response = $this
            ->actingAs($membership->user)
            ->patch(route('comments.update', ['current_team' => $membership->team, 'task' => $task, 'comment' => $comment]), ['body' => 'Changed']);

        $response->assertNotFound();
        expect($comment->fresh()->body)->toBe('Keep me');
    });
});

describe('destroy', function () {
    test('members delete their own comment', function () {
        $membership = Membership::factory()->role(TeamRole::Member)->create();
        $task = Task::factory()->for(Project::factory()->for($membership->team))->create();
        $comment = Comment::factory()->for($task)->for($membership->user, 'author')->create();

        $response = $this
            ->actingAs($membership->user)
            ->from(route('tasks.show', ['current_team' => $membership->team, 'task' => $task]))
            ->delete(route('comments.destroy', ['current_team' => $membership->team, 'task' => $task, 'comment' => $comment]));

        $response->assertRedirect(route('tasks.show', ['current_team' => $membership->team, 'task' => $task]));
        $response->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Comment deleted.']);
        expect($comment->fresh())->toBeNull();
    });

    test('an admin deletes someone else\'s comment', function () {
        $membership = Membership::factory()->role(TeamRole::Admin)->create();
        $task = Task::factory()->for(Project::factory()->for($membership->team))->create();
        $comment = Comment::factory()->for($task)->create();

        $this
            ->actingAs($membership->user)
            ->delete(route('comments.destroy', ['current_team' => $membership->team, 'task' => $task, 'comment' => $comment]))
            ->assertRedirect();

        expect($comment->fresh())->toBeNull();
    });

    test('members cannot delete a comment someone else wrote', function () {
        $membership = Membership::factory()->role(TeamRole::Member)->create();
        $task = Task::factory()->for(Project::factory()->for($membership->team))->create();
        $comment = Comment::factory()->for($task)->create();

        $response = $this
            ->actingAs($membership->user)
            ->delete(route('comments.destroy', ['current_team' => $membership->team, 'task' => $task, 'comment' => $comment]));

        $response->assertForbidden();
        expect($comment->fresh())->not->toBeNull();
    });

    test('a comment in an archived project cannot be deleted', function () {
        $membership = Membership::factory()->role(TeamRole::Owner)->create();
        $task = Task::factory()->for(Project::factory()->for($membership->team)->archived())->create();
        $comment = Comment::factory()->for($task)->for($membership->user, 'author')->create();

        $response = $this
            ->actingAs($membership->user)
            ->delete(route('comments.destroy', ['current_team' => $membership->team, 'task' => $task, 'comment' => $comment]));

        $response->assertForbidden();
        expect($comment->fresh())->not->toBeNull();
    });

    test('a comment of another team returns 404', function () {
        $membership = Membership::factory()->role(TeamRole::Owner)->create();
        $comment = Comment::factory()->create();

        $response = $this
            ->actingAs($membership->user)
            ->delete(route('comments.destroy', ['current_team' => $membership->team, 'task' => $comment->task_id, 'comment' => $comment]));

        $response->assertNotFound();
        expect($comment->fresh())->not->toBeNull();
    });
});
