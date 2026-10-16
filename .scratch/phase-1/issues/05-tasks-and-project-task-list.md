# Tasks and the project task list

Status: ready-for-agent
Blocked by: 04

Members create and update tasks in a project and browse them in a table.

## Scope

- `TaskStatus` and `TaskPriority` enums (spec values; defaults Backlog and None).
- `Task` model, migration, factory, `TaskPolicy`: `project_id`, `number`, `title`, `description`, `status`, `priority`, `assignee_id`, `author_id`, `due_date`. Unique index on `project_id, number`.
- `reference` accessor: `{project identifier}-{number}`.
- Actions: `CreateTask` (takes `next_task_number` under `lockForUpdate()` in a transaction, increments it), `UpdateTask`, `DeleteTask`.
- Policy: create/update by permission; delete by permission or, for Member, only as the author; nothing in an archived project.
- Validation: assignee must be a member of the project's team.
- Extend `DeleteProject` to delete the project's tasks.
- Project tasks page: table with filters (status, priority, assignee), search by title or task reference, sorting, server-side pagination; create task in a dialog.

## UI decision

Prototype variant **B, data table** won over a Linear-style grouped list (A) and a list with a preview pane (C). Reference: `resources/js/components/prototype/project-tasks/variant-b.tsx` on the `prototype/screens` branch. Rewrite it with shadcn components; do not copy the prototype.

- Header: project name, identifier badge, description; actions Edit project, Archive, New task (shown per permission).
- Toolbar: search input (title or task reference) and faceted filters for status, priority and assignee (incl. Unassigned), following the shadcn "Tasks" example: a dashed outline button per facet opens `Popover` + `Command` with multi-select checkboxes and a task count per value; selected values show as badges inside the button; Reset clears all. Multiple values go to the query string as arrays (`?status[]=todo&status[]=in_progress`). No TanStack Table.
- Columns: ID (task reference), Title, Status (badge with icon), Priority (icon + label), Assignee (avatar + name), Due (red when overdue), Updated. Sortable by clicking the header.
- Footer: "Showing X–Y of Z" and previous/next pagination.
- Built with shadcn `table` and `pagination` (add via the shadcn CLI; no new npm dependencies). No TanStack Table: filtering, sorting and pagination happen on the server.
- Table state lives in the query string (`?search=…&status[]=todo&priority[]=high&assignee[]=…&sort=-priority&page=2`); changes go through `router.get(…, { preserveState: true, preserveScroll: true, replace: true })`, search is debounced.
- Fixed page size of 25.
- Only the task title is a `<Link>` to the task detail; rows just highlight on hover.
- The table is read-only; tasks are edited on the task detail page.

## Acceptance criteria

- [ ] Feature tests: numbering is sequential per project and not reused after deletion; Member can delete only own tasks; Viewer cannot create or update; archived project rejects writes; assignee from another team is rejected; cross-team access returns 404; filters and search return the right tasks.
- [ ] `composer ci:check` passes.
