# Task detail page and markdown rendering

Status: ready-for-agent
Blocked by: 05

A dedicated page for one task, with its description rendered as markdown.

## Scope

- Install `react-markdown` and `remark-gfm` (approved) and a shared markdown component; raw HTML is not rendered.
- Route `/{current_team}/tasks/{task}`, task bound by UUID and scoped to the current team.
- Page shows the task reference, title, rendered description, status, priority, assignee, author and due date, with inline editing of the fields for members who may update; delete action per the Policy.
- Task rows in the project task list link to this page.

## UI decision

Two prototype rounds on the `prototype/screens` branch (`resources/js/components/prototype/task-detail/`). Round 1 picked **inline editing** that saves immediately (A) over an explicit Edit mode (B) and an Edit dialog (C). Round 2 picked **layout D, document with property chips** (`layout-d.tsx`, editors in `editors.tsx`) over a labelled property sheet (E), a header band with a sticky toolbar (F) and a comments side panel (G). Rewrite with shadcn components; do not copy the prototype.

- Single narrow column: meta line (task reference · author · updated, `⋯` menu), title, property chips, description, comments.
- Title edits in place; Enter or blur saves.
- Property chips save on change: status and priority (`Select`), assignee (combobox: `Popover` + `Command`, searchable, includes Unassigned), due date (`Popover` + `Calendar`, with Clear).
- Description opens in a `Textarea` on click with explicit Save / Cancel (⌘↵ / Esc).
- `⋯` menu: Copy reference, Delete task (confirmed with `AlertDialog`).
- Viewer sees the same page with every editor disabled.
- Uses the shadcn components added on the prototype branch: `popover`, `command`, `calendar`, `alert-dialog`, `textarea` (see Frontend dependencies in `spec.md`).
- Dates formatted with `date-fns`; comment and "updated" times are relative ("3 days ago") with the absolute date in a tooltip.

## Acceptance criteria

- [ ] Feature tests: page renders for every role; cross-team access returns 404.
- [ ] `composer ci:check` passes.
