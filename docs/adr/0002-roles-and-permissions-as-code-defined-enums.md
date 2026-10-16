# Roles and permissions as code-defined enums

Roles (`TeamRole`: Owner, Admin, Member, Viewer) and the permissions each grants (`TeamPermission`) are PHP enums with the role-to-permission mapping in code, enforced by Policies; a member's role is stored as a string on the membership. We extend what the starter kit ships instead of adopting spatie/laravel-permission (optionally with binary-cats/laravel-rbac on top) because the role set is fixed, Spatie's teams mode would duplicate the role already stored on `team_members` and force rewriting the starter kit's teams feature, and keeping the mapping in our own code makes the authorization chain explicit and easy to reuse from MCP tools.

## Consequences

Roles cannot be customised per team at runtime. If team-defined roles are ever needed, revisit this decision; that is the point at which a database-backed package like spatie/laravel-permission earns its place.
