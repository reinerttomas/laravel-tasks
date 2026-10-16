# Comments

Status: ready-for-agent
Blocked by: 06

Members discuss a task in a comment thread on the task detail page.

## Scope

- `Comment` model, migration, factory, `CommentPolicy`: `task_id`, `author_id`, `body` (markdown).
- Actions: `CreateComment`, `UpdateComment`, `DeleteComment`.
- Policy: create by permission; update by the author only; delete by permission or, for Member, only as the author; nothing in an archived project.
- Extend `DeleteTask` and `DeleteProject` to delete comments first.
- Thread on the task detail page: rendered markdown, author and time, add/edit/delete per the Policy.

## Acceptance criteria

- [ ] Feature tests cover the matrix for comments (including Admin cannot edit someone else's comment but can delete it), archived project rejecting writes, and deleting a task or project removing its comments.
- [ ] `composer ci:check` passes.
