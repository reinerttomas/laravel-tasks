<?php

declare(strict_types=1);

use App\Enums\TaskStatus;
use App\Enums\TeamRole;
use App\Models\Membership;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Support\Collection;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $response = $this
        ->actingAs($user)
        ->get(route('dashboard'));

    $response->assertOk();
});

test('dashboard includes pending invitations for the authenticated user', function () {
    $owner = User::factory()->create(['name' => 'Taylor Otwell']);
    $invitedUser = User::factory()->create(['email' => 'invited@example.com']);
    $team = Team::factory()->create(['name' => 'Laravel Team']);

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $invitation = TeamInvitation::factory()->create([
        'team_id' => $team->id,
        'email' => 'invited@example.com',
        'invited_by' => $owner->id,
    ]);

    $response = $this
        ->actingAs($invitedUser)
        ->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('dashboard')
        ->has('pendingInvitations', 1)
        ->where('pendingInvitations.0.code', $invitation->code)
        ->where('pendingInvitations.0.inviterName', 'Taylor Otwell')
        ->where('pendingInvitations.0.team.name', 'Laravel Team')
        ->where('pendingInvitations.0.team.slug', $team->slug)
        ->missing('pendingInvitations.0.teamName'),
    );
});

test('dashboard does not include accepted invitations', function () {
    $owner = User::factory()->create();
    $invitedUser = User::factory()->create(['email' => 'invited@example.com']);
    $team = Team::factory()->create();

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    TeamInvitation::factory()->accepted()->create([
        'team_id' => $team->id,
        'email' => 'invited@example.com',
        'invited_by' => $owner->id,
    ]);

    $response = $this
        ->actingAs($invitedUser)
        ->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('dashboard')
        ->has('pendingInvitations', 0),
    );
});

test('dashboard excludes expired invitations without deleting them', function () {
    $owner = User::factory()->create();
    $invitedUser = User::factory()->create(['email' => 'invited@example.com']);
    $team = Team::factory()->create();

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $invitation = TeamInvitation::factory()->expired()->create([
        'team_id' => $team->id,
        'email' => 'invited@example.com',
        'invited_by' => $owner->id,
    ]);

    $response = $this
        ->actingAs($invitedUser)
        ->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('dashboard')
        ->has('pendingInvitations', 0),
    );

    $this->assertDatabaseHas('team_invitations', [
        'id' => $invitation->id,
    ]);
});

test('dashboard does not include or delete other users invitations', function () {
    $owner = User::factory()->create();
    $invitedUser = User::factory()->create(['email' => 'invited@example.com']);
    $team = Team::factory()->create();

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $invitation = TeamInvitation::factory()->expired()->create([
        'team_id' => $team->id,
        'email' => 'someone@example.com',
        'invited_by' => $owner->id,
    ]);

    $response = $this
        ->actingAs($invitedUser)
        ->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('dashboard')
        ->has('pendingInvitations', 0),
    );

    $this->assertDatabaseHas('team_invitations', [
        'id' => $invitation->id,
    ]);
});

test('visiting another team dashboard switches the current team', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Member->value]);

    $response = $this
        ->actingAs($user)
        ->get(route('dashboard', ['current_team' => $team->slug]));

    $response->assertOk();
    expect($user->fresh()->current_team_id)->toBe($team->id);
});

test('the dashboard counts the tasks of the current team by status, without archived projects', function () {
    $membership = Membership::factory()->role(TeamRole::Viewer)->create();
    $project = Project::factory()->for($membership->team)->create();
    Task::factory()->for($project)->create(['status' => TaskStatus::Backlog]);
    Task::factory()->for($project)->count(2)->create(['status' => TaskStatus::Todo]);
    Task::factory()->for(Project::factory()->for($membership->team))->create(['status' => TaskStatus::Done]);
    Task::factory()->for(Project::factory()->for($membership->team)->archived())->create(['status' => TaskStatus::Todo]);
    Task::factory()->create(['status' => TaskStatus::Todo]);

    $response = $this
        ->actingAs($membership->user)
        ->get(route('dashboard', ['current_team' => $membership->team]));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('teamTaskCounts', ['backlog' => 1, 'todo' => 2, 'in_progress' => 0, 'done' => 1, 'canceled' => 0]),
    );
});

test('the dashboard groups my open tasks in the current team into overdue, in progress and up next', function () {
    $membership = Membership::factory()->role(TeamRole::Member)->create();
    $me = $membership->user;
    $project = Project::factory()->for($membership->team)->create();
    $yesterday = today()->subDay()->toDateString();
    $mine = fn (TaskStatus $status, ?string $dueDate, string $title) => Task::factory()->for($project)->for($me, 'assignee')
        ->create(['status' => $status, 'due_date' => $dueDate, 'title' => $title]);

    $mine(TaskStatus::InProgress, $yesterday, 'Late and started');
    $mine(TaskStatus::Backlog, $yesterday, 'Late and not started');
    $mine(TaskStatus::Done, $yesterday, 'Finished late');
    $mine(TaskStatus::InProgress, null, 'Started');
    $mine(TaskStatus::Todo, today()->toDateString(), 'Due today');
    $mine(TaskStatus::Todo, null, 'Waiting');
    $mine(TaskStatus::Backlog, null, 'Someday');
    Task::factory()->for($project)->create(['status' => TaskStatus::Todo, 'title' => 'Not mine']);
    Task::factory()->for(Project::factory()->for($membership->team)->archived())->for($me, 'assignee')
        ->create(['status' => TaskStatus::Todo, 'title' => 'In an archived project']);
    Task::factory()->for(Project::factory()->for(Membership::factory()->for($me)->create()->team))->for($me, 'assignee')
        ->create(['status' => TaskStatus::Todo, 'title' => 'In another team']);

    $response = $this
        ->actingAs($me)
        ->get(route('dashboard', ['current_team' => $membership->team]));

    $titles = fn (array $expected) => fn (Collection $tasks) => $tasks->pluck('title')->sort()->values()->all() === $expected;

    $response->assertInertia(fn (Assert $page) => $page
        ->where('myTasks.overdue', $titles(['Late and not started', 'Late and started']))
        ->where('myTasks.inProgress', $titles(['Started']))
        ->where('myTasks.upNext', $titles(['Due today', 'Waiting']))
        ->where('myTasks.overdue.0.project.color', $project->color->value),
    );
});
