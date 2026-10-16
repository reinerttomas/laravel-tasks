# Projects

Status: ready-for-agent
Blocked by: 03

Members can list projects; Owner and Admin manage them.

## Scope

- `Project` model, migration, factory, `ProjectPolicy`: `team_id`, `name`, `identifier`, `description`, `archived_at`, `next_task_number` (starts at 1).
- `identifier`: 2–5 uppercase letters, unique per team (DB unique index on `team_id, identifier`), editable.
- Actions: `CreateProject`, `UpdateProject`, `ArchiveProject`, `RestoreProject`, `DeleteProject` (Owner only; in one transaction, deletes the project's comments, tasks, then the project; written now, extended as tasks and comments arrive).
- Routes under `/{current_team}/projects`, project bound by UUID and scoped to the current team (another team's project → 404).
- Projects page: active projects, archived behind a filter; create and edit in a dialog; archive, restore and delete with confirmation; actions hidden when the member lacks the permission.

## Acceptance criteria

- [ ] Feature tests cover each action per role (allowed and forbidden), identifier validation and uniqueness per team, and cross-team access returning 404.
- [ ] `composer ci:check` passes.
