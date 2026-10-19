#!/usr/bin/env bash
# Superset workspace setup: runs inside a freshly created git worktree.
# Copies gitignored local files from the main checkout, gives the workspace its own
# seeded database, installs dependencies and serves it through Herd.
set -euo pipefail

source "$(dirname "${BASH_SOURCE[0]}")/lib.sh"

if is_main_checkout; then
  echo "Main checkout — nothing to set up."
  exit 0
fi

copy_file() {
  local rel="$1"
  if [[ ! -e "$MAIN_CHECKOUT/$rel" ]]; then
    echo "  skip $rel (missing in main checkout)"
    return 0
  fi
  mkdir -p "$(dirname "$rel")"
  cp "$MAIN_CHECKOUT/$rel" "$rel"
}

copy_dir() {
  local rel="$1"
  [[ -d "$MAIN_CHECKOUT/$rel" ]] || return 0
  mkdir -p "$rel"
  rsync -a --delete "$MAIN_CHECKOUT/$rel/" "$rel/"
}

echo "==> Local-only files from $MAIN_CHECKOUT"
for file in \
  .env \
  AGENTS.md \
  boost.json \
  .mcp.json \
  .claude/settings.local.json
do
  copy_file "$file"
done
copy_dir .claude/skills
copy_dir .agents/skills

echo "==> Database $DATABASE"
sed -i '' -E "s/^DB_DATABASE=.*/DB_DATABASE=$DATABASE/" .env
if [[ "$(pg -tAc "SELECT 1 FROM pg_database WHERE datname = '$DATABASE'")" != "1" ]]; then
  pg -c "CREATE DATABASE \"$DATABASE\""
fi

echo "==> Dependencies, assets and Wayfinder types"
composer setup:worktree

echo "==> Schema and demo data"
php artisan migrate:fresh --seed --force

echo "==> Herd site https://$SITE.test"
herd link --secure --update-env -n "$SITE"

echo "Workspace ready: https://$SITE.test (owner@example.com / password)"
