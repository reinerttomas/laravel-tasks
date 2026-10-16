# My tasks and the dashboard

Status: ready-for-agent
Blocked by: 05

Give each member an overview of their own work in the current team.

## Scope

- **My tasks** page `/{current_team}/my-tasks`: tasks assigned to me across all projects of the current team (archived projects excluded), with the same filters, sorting and pagination as the project task list; added to the sidebar.
- **Dashboard**: task counts by status for the current team and a list of my In Progress tasks.

## Acceptance criteria

- [ ] Feature tests: only my tasks from the current team appear; counts are scoped to the current team.
- [ ] `composer ci:check` passes.
