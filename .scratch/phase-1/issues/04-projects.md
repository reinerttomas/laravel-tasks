# Projects

Status: ready-for-agent
Blocked by: 03

Members can list projects; Owner and Admin manage them.

## Scope

- `Project` model, migration, factory, `ProjectPolicy`: `team_id`, `name`, `identifier`, `description`, `archived_at`, `next_task_number` (starts at 1).
- `identifier`: 2–5 uppercase letters, unique per team (DB unique index on `team_id, identifier`), editable.
- `color`: `ProjectColor` enum (slate, red, orange, amber, green, teal, sky, blue, violet, pink), stored as a string, required. The create dialog preselects the first colour not used by an active project in the team. The frontend maps each value to a static Tailwind class.
- Sidebar: a "Projects" group under the main navigation listing the current team's active projects (colour swatch, identifier, name) linking to their task lists, fed by an Inertia shared prop.
- Actions: `CreateProject`, `UpdateProject`, `ArchiveProject`, `RestoreProject`, `DeleteProject` (Owner only; in one transaction, deletes the project's comments, tasks, then the project; written now, extended as tasks and comments arrive).
- Routes under `/{current_team}/projects`, project bound by UUID and scoped to the current team (another team's project → 404).
- Projects page: active projects, archived behind a filter; create and edit in a dialog; archive, restore and delete with confirmation; actions hidden when the member lacks the permission.

## UI decision

Prototype variant **B, card grid** won over a table with tabs (A) and a list with a project overview (C). Reference: `resources/js/components/prototype/projects/` on the `prototype/screens` branch (`variant-b.tsx`, dialogs in `shared.tsx`). Rewrite with shadcn components; do not copy the prototype.

- Header: "Projects" title, Active / Archived `ToggleGroup`, "New project" button (Owner, Admin). No "new project" tile in the grid.
- Responsive grid of `Card`s: identifier on a square in the project colour, name, one-line description, `⋯` actions menu (Edit, Archive / Restore, Delete), a stacked status bar (Done, In Progress, Todo, Backlog; tooltip with counts per segment), counts line, footer with open task count and updated date. Archived cards are dimmed and show "Archived {date}".
- Clicking a card opens the project task list.
- Create / edit `Dialog`: name, colour (swatches in a `ToggleGroup` with a live preview of the identifier square), identifier (suggested from the name until edited, uppercase letters only, 2–5, unique in the team, preview of a task reference, warning when renaming an existing identifier that references change and old links break), description.
- Archive / restore confirmed with `AlertDialog`; delete (Owner only) with `AlertDialog` that requires typing the project identifier.
- Empty project shows "No tasks yet"; empty filter shows "No active / archived projects."
- The page needs per-project task counts by status: load them with one grouped query (`withCount` per status or a `groupBy`), not per card.

## Acceptance criteria

- [ ] Feature tests cover each action per role (allowed and forbidden), identifier validation and uniqueness per team, and cross-team access returning 404.
- [ ] `composer ci:check` passes.
