# Laravel Tasks

A multi-tenant task management SaaS, a simplified Linear: teams organise their work into projects made of tasks.

## Tenancy

**Team**:
The tenant: the unit that owns projects, tasks and comments, and that every piece of work is scoped to.
_Avoid_: Organization, workspace, company, account

**Personal team**:
A team created automatically for a user at registration, so they can start working without being invited.
_Avoid_: Default team, private workspace

**Current team**:
The team a user is working in right now, chosen through the team switcher.
_Avoid_: Active team, selected team

**Membership**:
A user's belonging to a team, carrying exactly one role in that team.
_Avoid_: Seat, team user

**Member**:
A user who holds a membership in a given team, regardless of role. Not to be confused with the Member role.
_Avoid_: Teammate, participant

**Invitation**:
An offer, sent by email, for someone to join a team with a predetermined role.
_Avoid_: Invite link, join request

## Access

**Role**:
One of a fixed set of levels a member holds within a team: Owner, Admin, Member or Viewer.
_Avoid_: Group, position

**Viewer**:
The role that can read everything in a team but change nothing.
_Avoid_: Guest, read-only member

**Permission**:
A named capability that a role grants, such as creating a project or deleting a task.
_Avoid_: Ability, right, privilege

## Work

**Project**:
A named body of work within a team that groups related tasks. Every member of the team can see every project.
_Avoid_: Board, space, repository

**Project identifier**:
A short uppercase code, unique within the team, that prefixes the references of the project's tasks (the `WEB` in `WEB-42`).
_Avoid_: Key, prefix, code

**Archived project**:
A project set aside: hidden from the default project list and read-only, but restorable.
_Avoid_: Closed project, inactive project

**Task**:
A single unit of work within a project, with a status, a priority and optionally an assignee.
_Avoid_: Issue, ticket, todo, item

**Task number**:
The sequential number of a task within its project, never reused.
_Avoid_: Task ID, index

**Task reference**:
The human-readable handle of a task, formed from the project identifier and the task number (`WEB-42`).
_Avoid_: Task identifier, task key, task code

**Status**:
Where a task stands in the fixed workflow: Backlog, Todo, In Progress, Done or Canceled.
_Avoid_: State, column, stage

**Priority**:
How urgent a task is: None, Urgent, High, Medium or Low.
_Avoid_: Severity, importance

**Assignee**:
The member responsible for completing a task. A task has at most one.
_Avoid_: Owner, responsible, worker

**Author**:
The user who created a task or wrote a comment.
_Avoid_: Creator, reporter, owner

**Comment**:
A markdown message attached to a task, forming the task's discussion.
_Avoid_: Note, reply, message
