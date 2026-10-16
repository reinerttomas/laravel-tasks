# Phase 1: team task management

A simplified Linear on top of the starter kit's teams feature: members of a team manage projects, tasks and comments, with access governed by their role. Vocabulary follows `GLOSSARY.md`; architectural decisions are in `docs/adr/0001`–`0004`.

## Goals

- Learn the core of a SaaS application: tenancy, roles, permissions, invitations.
- Keep all business logic in Actions and all authorization in Policies, so the MCP server in phase 2 can call the same code without bypassing permissions.

## Tenancy and users

- A user can belong to many teams, with one role per team; the current team is in the URL as its slug (starter kit behaviour, kept).
- Registration creates a personal team (starter kit behaviour, kept).
- All tenant data lives in one database, scoped by `team_id` (ADR 0001).
- Every model uses a UUIDv7 primary key (ADR 0003). The starter kit's migrations are edited in place and the database is rebuilt with `migrate:fresh`.
- No platform super-admin, no billing.

## Roles and permissions

Roles are the `TeamRole` enum, extended with **Viewer**; permissions are the `TeamPermission` enum, extended with project, task and comment permissions (ADR 0002). Viewer is assignable in invitations and member role changes.

| Action                                            |  Owner   |  Admin   |  Member  | Viewer |
| ------------------------------------------------- | :------: | :------: | :------: | :----: |
| Read projects, tasks, comments                    |    ✅    |    ✅    |    ✅    |   ✅   |
| Create, update, archive, restore a project        |    ✅    |    ✅    |    ❌    |   ❌   |
| Delete a project                                  |    ✅    |    ❌    |    ❌    |   ❌   |
| Create and update a task (incl. status, assignee) |    ✅    |    ✅    |    ✅    |   ❌   |
| Delete a task                                     |    ✅    |    ✅    | own only |   ❌   |
| Add a comment                                     |    ✅    |    ✅    |    ✅    |   ❌   |
| Edit a comment                                    | own only | own only | own only |   ❌   |
| Delete a comment                                  |    ✅    |    ✅    | own only |   ❌   |

"Own only" is a Policy condition on the model's author, on top of the role's permission. Nothing in an archived project can be created or changed (tasks, comments), only read.

## Projects

- Fields: `name`, `identifier` (2–5 uppercase letters, unique within the team, editable), `color` (one of a fixed palette: slate, red, orange, amber, green, teal, sky, blue, violet, pink; `ProjectColor` enum stored as a string), `description` (nullable), `archived_at` (nullable), `next_task_number`.
- A new project defaults to the first palette colour not used by an active project in the team; the member can pick another.
- Archive hides the project from the default list and makes it read-only; restore reverses it.
- Delete is Owner-only: `DeleteProject` removes the project's comments, tasks and the project in one transaction. No `cascadeOnDelete`, no soft deletes.
- Routes use the project's UUID: `/{current_team}/projects/{project}`. A project from another team must 404.

## Tasks

- Fields: `title`, `description` (markdown, nullable), `number`, `status`, `priority`, `assignee_id` (nullable), `author_id`, `due_date` (date, nullable).
- `status` enum: Backlog, Todo, In Progress, Done, Canceled (ADR 0004). New tasks default to Backlog.
- `priority` enum: None, Urgent, High, Medium, Low. New tasks default to None.
- `number` is unique within the project, taken from the project's `next_task_number` under `lockForUpdate()` inside the creating transaction, and never reused.
- The task reference (`$task->reference`) is `{project identifier}-{number}`, computed and never stored, so changing the project identifier renumbers every reference.
- The assignee must be a member of the task's team. When a member leaves or is removed from a team, their tasks in that team become unassigned; they stay the author of their tasks and comments.
- Tasks do not move between projects.
- Routes use the task's UUID: `/{current_team}/tasks/{task}`.

## Comments

- Markdown body, belongs to a task and an author; editable by its author only, deletable per the matrix.
- No activity history.

## Frontend dependencies

Approved additions: `radix-ui` (single package replacing the individual `@radix-ui/react-*` packages, via `shadcn migrate radix`, so registry components install as published), `cmdk` (command / combobox), `react-day-picker` (calendar), `date-fns` (date formatting), `react-markdown` and `remark-gfm` (markdown rendering).

## Markdown

Task descriptions and comments are written in a plain `<textarea>` and rendered with `react-markdown` and `remark-gfm` (new frontend dependencies, approved). Raw HTML is not rendered.

## Screens

Build every screen from shadcn/ui components (`resources/js/components/ui`, style `new-york`) wherever one fits; add missing ones with the shadcn CLI rather than hand-rolling markup. Screen layouts were chosen from prototypes on the `prototype/screens` branch.

Sidebar navigation: Dashboard, My tasks, Projects, then a "Projects" group listing the current team's active projects (colour swatch + identifier + name), each linking to its task list. The list comes from Inertia shared props.

1. **Projects**: active projects, with archived projects behind a filter; create and edit in a dialog.
2. **Project tasks**: a table filterable by status, priority and assignee, searchable by title or task reference, sortable, paginated server-side.
3. **Task detail**: a page with inline editing of task fields and the comment thread.
4. **My tasks**: tasks assigned to me across all projects of the current team; the project task table with a Project column and filter instead of Assignee, open statuses by default.
5. **Dashboard**: task counts by status and my in-progress tasks.

## Demo data

`DatabaseSeeder` creates a demo team with four users (one per role, known password), 3 projects and about 50 tasks with comments.

## Out of scope

- **Phase 1.5:** notifications (e.g. task assignment).
- **Later:** team-defined roles, per-team statuses, labels, sub-tasks, cycles, attachments, activity history, kanban board, moving tasks between projects, super-admin, billing.
- **Phase 2:** MCP server and AI queries, designed in a separate session.
