# 01: Manual deploy workflow

**What to build:** A `deploy` workflow started by hand that runs the test suite, builds and pushes the production image tagged `sha-<short commit>`, moves `latest` onto it, starts a Dokploy deploy and waits for the result, with a job summary. The Compose stack switches to `latest` with `pull_policy: always` and drops `IMAGE_TAG`. Includes the Dokploy deploy script adapted from matchday without its env rewrite, a `workflow_call` trigger on the `tests` workflow and CI moved to PHP 8.5. See `spec.md`, Implementation Decisions.

**Blocked by:** None (can start immediately)

**Status:** resolved

- [x] `actionlint` passes on all workflows and `shellcheck` passes on the deploy script.
- [x] The deploy script never calls `compose.one`, `compose.saveEnvironment` or `compose.update`.
- [x] The deploy script exits with its documented code, before any API call, for missing env vars, invalid timeout or poll values and missing tools.
- [x] Against a stub Dokploy API the script reports `done`, fails on `error`, fails on timeout, survives one failed status read and ignores an older deployment with the same title.
- [x] The image job pushes only `sha-<short commit>` with OCI labels; `latest` moves in the deploy job, by retagging without a rebuild.
- [x] Dokploy secrets reach only the deploy step; every job has minimal permissions; actions are pinned to SHAs.
- [x] Every app service in `compose.yml` runs `:latest` with `pull_policy: always`; `IMAGE_TAG` is gone from `compose.yml` and `.env.dokploy`; the stack still comes up healthy locally.
- [x] The `tests` workflow runs on PHP 8.5 and `composer test` passes locally.

## Comments

- 2026-10-20: Implemented on branch `deploy/01`, merged into `feat/docker`. `tests.yml` cancels in-progress runs only outside manual dispatch, so a second deploy cannot cancel the first one's CI. The script takes an optional `DOKPLOY_DEPLOY_TITLE` (the workflow sets `Deploy sha-…`). Rollback steps are a comment at the top of `deploy.yml`. The local stack check used `--pull never`, since `pull_policy: always` would pull from GHCR.
