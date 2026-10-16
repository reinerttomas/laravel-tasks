# Align the starter kit with project rules

Status: ready-for-agent

Bring the starter kit's application code in line with `.ai/rules` before new features build on it. Behaviour must not change; the existing Auth, Settings and Teams tests are the safety net.

## Scope

- Flatten `app/Actions`: move `Actions/Teams/CreateTeam` and `Actions/Fortify/*` directly under `app/Actions` (update `FortifyServiceProvider` bindings). Final readonly classes, no `auth()`/`request()`/`session()` inside.
- Extract team operations still living in controllers into Actions (e.g. update team, delete team, switch team, leave team, update member role, remove member, create/cancel/accept/decline invitation). Controllers become thin and delegate.
- Models (`User`, `Team`, `Membership`, `TeamInvitation`): `final`, Eloquent attributes (`#[Fillable]`, `#[UseFactory]`, `#[UsePolicy]`), `casts()`, `@property-read` docblocks, typed relationships, `#[Scope]` scopes, no `$with`.
- Add the missing `MembershipFactory`.
- Existing migrations are left as they are here; issue 02 rewrites them.

## Acceptance criteria

- [ ] All existing tests pass unchanged (except for namespace imports).
- [ ] `composer test` passes.
