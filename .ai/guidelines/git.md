## Git

- Commit messages MUST follow Conventional Commits: `<type>(<optional scope>): <description>`.
- Allowed types: `feat`, `fix`, `chore`, `refactor`, `docs`, `test`, `ci`, `build`, `perf`, `style`.
- Description is imperative, lowercase, no trailing period. Breaking changes use `!` after the type/scope or a `BREAKING CHANGE:` footer.
- Examples: `feat(auth): add passkey login`, `chore: install nunomaduro/essentials`, `fix(api)!: rename user id field`.
- Before running `git commit`, run `composer test` (pint --test, vp check, phpstan, tsc --noEmit, type coverage, pest with coverage) and commit only when it passes. Fix failures first; never skip or bypass the check. This mirrors CI in `.github/workflows/tests.yml`.
- In a fresh git worktree, run `composer setup:worktree` before anything else (copies `.env` from the main checkout, installs dependencies, builds assets and Wayfinder types; no migrations).
