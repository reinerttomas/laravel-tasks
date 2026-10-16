# Demo seeder

Status: ready-for-agent
Blocked by: 07

Seed realistic data for trying out roles by hand and, in phase 2, for AI queries.

## Scope

- `DatabaseSeeder` creates a demo team with four users, one per role (Owner, Admin, Member, Viewer), with known emails and the password `password`.
- 3 projects (one archived), about 50 tasks spread across statuses, priorities and assignees, with comments by different authors.
- Uses factories and goes through the creating Actions where numbering matters, so task numbers and `next_task_number` stay consistent.

## Acceptance criteria

- [ ] `php artisan migrate:fresh --seed` succeeds and each demo user can log in.
- [ ] `composer ci:check` passes.
