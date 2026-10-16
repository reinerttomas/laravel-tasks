# UUID primary keys on every model

Every model, including the starter kit's `User`, `Team`, `Membership` and `TeamInvitation`, uses a UUIDv7 primary key via `HasUuids`, stored in PostgreSQL's native `uuid` type. UUIDs keep record counts out of URLs and make IDs unguessable for routes and for the MCP server in phase 2, as defence in depth on top of Policies, which remain the actual access control. We chose UUIDs everywhere over UUIDs only on new models (two conventions in one schema) and over a bigint primary key with a separate public UUID column (two identifiers per model), and v7 over v4 so that time-ordered keys keep B-tree indexes compact.

## Consequences

The starter kit's migrations were edited in place before the first deployment, along with every column that references users (e.g. `sessions.user_id`, `passkeys.user_id`); any future polymorphic relation (such as a notifications table) must use `uuidMorphs`.
