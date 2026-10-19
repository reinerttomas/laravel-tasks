#!/usr/bin/env bash
# Shared helpers for Superset workspace lifecycle scripts. Sourced, not executed.

export PATH="$HOME/Library/Application Support/Herd/bin:$PATH"

WORKTREE="$(pwd)"
MAIN_CHECKOUT="$(git worktree list --porcelain | head -n1 | sed 's/^worktree //')"
WORKSPACE_NAME="$(basename "$WORKTREE")"

# Hostname-safe name for the Herd site (https://<SITE>.test).
SITE="$(printf '%s' "$WORKSPACE_NAME" | tr '[:upper:]' '[:lower:]' | sed -E 's/[^a-z0-9]+/-/g; s/^-+//; s/-+$//')"

# Identifier-safe name for the per-workspace development database.
DATABASE="laravel_tasks_$(printf '%s' "$WORKSPACE_NAME" | tr '[:upper:]' '[:lower:]' | sed -E 's/[^a-z0-9]+/_/g; s/^_+//; s/_+$//')"

is_main_checkout() {
  [[ "$WORKTREE" == "$MAIN_CHECKOUT" ]]
}

# Reads a key from .env, stripping optional surrounding quotes.
env_value() {
  grep -E "^$1=" .env | head -n1 | cut -d= -f2- | sed -E 's/^"(.*)"$/\1/; s/^'"'"'(.*)'"'"'$/\1/'
}

# Runs psql against the server's maintenance database with the credentials from .env.
pg() {
  PGPASSWORD="$(env_value DB_PASSWORD)" psql \
    -h "$(env_value DB_HOST)" \
    -p "$(env_value DB_PORT)" \
    -U "$(env_value DB_USERNAME)" \
    -d postgres \
    -v ON_ERROR_STOP=1 \
    "$@"
}
