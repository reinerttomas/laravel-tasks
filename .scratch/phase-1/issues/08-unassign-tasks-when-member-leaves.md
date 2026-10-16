# Unassign tasks when a member leaves the team

Status: ready-for-agent
Blocked by: 05

When a member leaves a team or is removed from it, their tasks in that team become unassigned.

## Scope

- The leave-team and remove-member Actions (from issue 01) set `assignee_id = null` on the departing user's tasks in that team, in the same transaction as removing the membership. Done explicitly in the Actions, not in an observer.
- Authorship of tasks and comments is unchanged.

## Acceptance criteria

- [ ] Feature tests: leaving and being removed both unassign tasks in that team only; tasks in the user's other teams keep their assignee.
- [ ] `composer ci:check` passes.
