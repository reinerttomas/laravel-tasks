# Align the starter kit with project rules

Status: resolved

Bring the starter kit's application code in line with `.ai/rules` before new features build on it. Behaviour must not change; the existing Auth, Settings and Teams tests are the safety net.

## Scope

- Flatten `app/Actions`: move `Actions/Teams/CreateTeam` and `Actions/Fortify/*` directly under `app/Actions` (update `FortifyServiceProvider` bindings). Final readonly classes, no `auth()`/`request()`/`session()` inside.
- Extract team operations still living in controllers into Actions (e.g. update team, delete team, switch team, leave team, update member role, remove member, create/cancel/accept/decline invitation). Controllers become thin and delegate.
- Models (`User`, `Team`, `Membership`, `TeamInvitation`): `final`, Eloquent attributes (`#[Fillable]`, `#[UseFactory]`, `#[UsePolicy]`), `casts()`, `@property-read` docblocks, typed relationships, `#[Scope]` scopes, no `$with`.
- Add the missing `MembershipFactory`.
- Existing migrations are left as they are here; issue 02 rewrites them.

## Acceptance criteria

- [x] All existing tests pass unchanged (except for namespace imports).
- [x] `composer test` passes.

## Comments

- 2026-10-17: Implemented on branch `phase-1/01`, merged into `feat/phase-1`. Review fixes in `phase-1/review`.
