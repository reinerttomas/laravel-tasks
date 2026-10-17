# Viewer role and the permission matrix

Status: resolved
Blocked by: 02

Extend roles and permissions to the full matrix in `spec.md` (ADR 0002).

## Scope

- Add `TeamRole::Viewer` (lowest level, assignable, label shown in the invitation and member role UI).
- Add project, task and comment permissions to `TeamPermission` and map them per role exactly as the spec's matrix says. Admin/Member/Viewer keep their current team, member and invitation permissions.
- The "own only" cases are not permissions; they are Policy conditions added with the models in later issues.
- Share the current member's permissions with the frontend (extend the existing `TeamPermissions` data object) so the UI can hide actions.

## Acceptance criteria

- [x] A user can be invited as, and changed to, Viewer.
- [x] A unit test asserts each role's permission set against the matrix.
- [x] `composer test` passes.

## Comments

- 2026-10-17: Implemented on branch `phase-1/03`, merged into `feat/phase-1`. Review fixes in `phase-1/review`.
