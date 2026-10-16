# My tasks and the dashboard

Status: ready-for-agent
Blocked by: 05

Give each member an overview of their own work in the current team.

## Scope

- **My tasks** page `/{current_team}/my-tasks`: tasks assigned to me across all projects of the current team (archived projects excluded), with the same filters, sorting and pagination as the project task list; added to the sidebar. Reuse the project task table component (no separate prototype): a Project column (colour swatch + identifier) replaces Assignee, the Project facet replaces the Assignee facet, and the status filter defaults to Backlog, Todo and In Progress.
- **Dashboard**: a personal focus page with the current team's task counts by status and my open tasks in three sections (see UI decision).

## UI decision

Two prototype rounds on the `prototype/screens` branch (`resources/js/components/prototype/dashboard/`). Round 1 picked **B, personal focus** over stat cards with lists (A) and a projects × statuses matrix (C). Round 2 kept **B** over stat cards with tabs (D), a side column with team numbers and my projects (E), grouping by due date (F) and quick status actions (G). Rewrite with shadcn components; do not copy the prototype.

- Single narrow column: greeting with the member's first name and a one-line summary ("You have N tasks in progress and M overdue.").
- A thin muted strip with the team's counts for Backlog, Todo, In Progress and Done (active projects of the current team).
- Three sections of my open tasks (assigned to me, active projects of the current team), each with a count and an empty state:
    - **Overdue**: due date before today, status Backlog, Todo or In Progress; red heading.
    - **In progress**: status In Progress, not overdue.
    - **Up next**: status Todo, not overdue.
- Each row: status icon, title (links to the task detail), task reference with the project colour dot, status label, priority icon, due date badge (red when overdue).
- Read-only: no status changes from the dashboard.
- "Today" is computed in the app timezone.

## Acceptance criteria

- [ ] Feature tests: only my tasks from the current team appear; counts are scoped to the current team and exclude archived projects; a task due yesterday appears only under Overdue.
- [ ] `composer test` passes.
