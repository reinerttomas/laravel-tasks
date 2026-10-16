# Team-scoped tenancy in a shared database

All teams share one PostgreSQL database; tenant data carries a `team_id` and is isolated by relationships (`$team->projects()`), route model binding scoped to the current team, and Policies. We rejected database-per-tenant (e.g. stancl/tenancy) because it adds connection switching, per-tenant migrations and provisioning infrastructure that this application does not need, while a shared schema stays simple to test and to query across teams for the MCP server in phase 2.

## Consequences

A missing `team_id` scope or a missing Policy check leaks data between teams, so every tenant query must go through the team relationship and every tenant route must be covered by a cross-team authorization test.
