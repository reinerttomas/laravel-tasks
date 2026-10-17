# Unassign tasks when a member leaves the team

Status: resolved
Blocked by: 05

When a member leaves a team or is removed from it, their tasks in that team become unassigned.

## Scope

- The leave-team and remove-member Actions (from issue 01) set `assignee_id = null` on the departing user's tasks in that team, in the same transaction as removing the membership. Done explicitly in the Actions, not in an observer.
- Authorship of tasks and comments is unchanged.

## Acceptance criteria

- [x] Feature tests: leaving and being removed both unassign tasks in that team only; tasks in the user's other teams keep their assignee.
- [x] `composer test` passes.

## Comments

- 2026-10-17: Implemented on branch `phase-1/08`, merged into `feat/phase-1`. Review fixes in `phase-1/review`.
