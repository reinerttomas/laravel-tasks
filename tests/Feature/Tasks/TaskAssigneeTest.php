<?php

declare(strict_types=1);

use App\Actions\CreateTask;
use App\Actions\UpdateTask;
use App\Models\Membership;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Validation\ValidationException;

test('a task cannot be created for an assignee outside the team', function () {
    $membership = Membership::factory()->create();
    $project = Project::factory()->for($membership->team)->create();
    $outsider = Membership::factory()->create()->user;

    expect(fn () => app(CreateTask::class)->handle($project, $membership->user, 'Fix the login form', ['assignee_id' => $outsider->id]))
        ->toThrow(ValidationException::class, 'The assignee must be a member of this team.')
        ->and($project->tasks()->exists())->toBeFalse();
});

test('a task cannot be assigned to someone outside the team', function () {
    $membership = Membership::factory()->create();
    $task = Task::factory()->for(Project::factory()->for($membership->team))->create(['assignee_id' => $membership->user->id]);
    $outsider = Membership::factory()->create()->user;

    expect(fn () => app(UpdateTask::class)->handle($task, ['assignee_id' => $outsider->id]))
        ->toThrow(ValidationException::class, 'The assignee must be a member of this team.')
        ->and($task->fresh()->assignee_id)->toBe($membership->user->id);
});
