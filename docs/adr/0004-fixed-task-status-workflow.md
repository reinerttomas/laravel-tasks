# Fixed task status workflow

A task's status is a PHP enum with a fixed workflow (Backlog, Todo, In Progress, Done, Canceled), shared by every team and project. Linear lets each team define its own statuses; we deliberately don't, because a fixed set keeps validation, filtering, the dashboard and the MCP tools in phase 2 simple and predictable. Per-team statuses (a `statuses` table with ordering and a category) are the expected path if a kanban board with custom columns is ever needed.
