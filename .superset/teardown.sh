#!/usr/bin/env bash
# Superset workspace teardown: drops what setup.sh created outside the worktree
# (Herd site, per-workspace database). Best effort, never fails the delete.
set -uo pipefail

source "$(dirname "${BASH_SOURCE[0]}")/lib.sh"

if is_main_checkout; then
  echo "Main checkout — nothing to tear down."
  exit 0
fi

echo "==> Herd unlink $SITE"
herd unlink "$SITE" || true
herd unsecure "$SITE" || true

if [[ -f .env ]]; then
  echo "==> Dropping database $DATABASE"
  pg -c "DROP DATABASE IF EXISTS \"$DATABASE\" WITH (FORCE)" || true
fi
