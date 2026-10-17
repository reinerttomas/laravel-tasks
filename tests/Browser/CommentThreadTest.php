<?php

declare(strict_types=1);

use App\Enums\TeamRole;
use App\Models\Comment;
use App\Models\Membership;
use App\Models\Project;
use App\Models\Task;

test('members add, edit and delete their comment in the thread', function () {
    $membership = Membership::factory()->role(TeamRole::Member)->create();
    $task = Task::factory()->for(Project::factory()->for($membership->team))->create();
    $this->actingAs($membership->user);

    $page = visit(route('tasks.show', ['current_team' => $membership->team, 'task' => $task], absolute: false));

    $page->assertSee('No comments yet.')
        ->fill('@comment-input', 'Reproduced on **Safari**.')
        ->click('@comment-submit')
        ->assertSourceHas('<strong>Safari</strong>')
        ->assertValue('@comment-input', '');

    $page->click('@comment-actions')
        ->click('@comment-edit')
        ->assertScript('document.activeElement?.dataset.test', 'comment-edit-input')
        ->fill('@comment-edit-input', 'Reproduced on **Firefox**.')
        ->click('@comment-edit-save')
        ->assertSourceHas('<strong>Firefox</strong>');

    expect($task->comments()->sole()->body)->toBe('Reproduced on **Firefox**.');

    $page->click('@comment-actions')
        ->click('@comment-delete')
        ->click('@comment-delete-confirm')
        ->assertSee('Comment deleted.')
        ->assertSee('No comments yet.')
        ->assertNoJavaScriptErrors();

    expect($task->comments()->exists())->toBeFalse();
});

test('viewers read comments, including those of deleted users, without a way to write', function () {
    $membership = Membership::factory()->role(TeamRole::Viewer)->create();
    $task = Task::factory()->for(Project::factory()->for($membership->team))->create();
    Comment::factory()->for($task)->create(['author_id' => null, 'body' => 'Fixed in **#12**.']);
    $this->actingAs($membership->user);

    $page = visit(route('tasks.show', ['current_team' => $membership->team, 'task' => $task], absolute: false));

    $page->assertSeeIn('@comment', 'Deleted user')
        ->assertSourceHas('<strong>#12</strong>')
        ->assertMissing('@comment-input')
        ->assertMissing('@comment-actions')
        ->assertNoJavaScriptErrors();
});
