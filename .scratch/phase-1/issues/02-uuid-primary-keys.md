# UUID primary keys on every model

Status: resolved
Blocked by: 01

Switch every model to UUIDv7 primary keys (ADR 0003).

## Scope

- Add `HasUuids` to `User`, `Team`, `Membership`, `TeamInvitation`.
- Edit the starter kit migrations in place: `uuid('id')->primary()`, and every referencing column as UUID (`team_members`, `team_invitations`, `users.current_team_id`, `sessions.user_id`, `passkeys.user_id`). Use `foreignIdFor(Model::class)->constrained()`, which creates a `uuid` column for `HasUuids` models.
- Remove `cascadeOnDelete()` and `down()` from the starter kit migrations while touching them (`.ai/rules/migrations.md`).
- Update `.ai/rules/migrations.md`: `foreignIdFor()` handles UUID keys too, so the manual `foreign()` fallback no longer applies to UUIDs.
- Update `@property-read` types and frontend types (`types/*.ts`) from `number` to `string` IDs.
- Rebuild the database with `migrate:fresh`.

## Acceptance criteria

- [x] Every table's primary key is `uuid`; new records get UUIDv7 IDs.
- [x] All existing tests pass.
- [x] `composer test` passes.

## Comments

- 2026-10-17: Implemented on branch `phase-1/02`, merged into `feat/phase-1`. Review fixes in `phase-1/review`.
