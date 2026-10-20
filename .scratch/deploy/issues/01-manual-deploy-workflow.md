# 01: Manual deploy workflow

**What to build:** A `deploy` workflow started by hand that runs the test suite, builds and pushes the production image tagged `sha-<short commit>`, moves `latest` onto it, starts a Dokploy deploy and waits for the result, with a job summary. The Compose stack switches to `latest` with `pull_policy: always` and drops `IMAGE_TAG`. Includes the Dokploy deploy script adapted from matchday without its env rewrite, a `workflow_call` trigger on the `tests` workflow and CI moved to PHP 8.5. See `spec.md`, Implementation Decisions.

**Blocked by:** None (can start immediately)

**Status:** ready-for-agent

- [ ] `actionlint` passes on all workflows and `shellcheck` passes on the deploy script.
- [ ] The deploy script never calls `compose.one`, `compose.saveEnvironment` or `compose.update`.
- [ ] The deploy script exits with its documented code, before any API call, for missing env vars, invalid timeout or poll values and missing tools.
- [ ] Against a stub Dokploy API the script reports `done`, fails on `error`, fails on timeout, survives one failed status read and ignores an older deployment with the same title.
- [ ] The image job pushes only `sha-<short commit>` with OCI labels; `latest` moves in the deploy job, by retagging without a rebuild.
- [ ] Dokploy secrets reach only the deploy step; every job has minimal permissions; actions are pinned to SHAs.
- [ ] Every app service in `compose.yml` runs `:latest` with `pull_policy: always`; `IMAGE_TAG` is gone from `compose.yml` and `.env.dokploy`; the stack still comes up healthy locally.
- [ ] The `tests` workflow runs on PHP 8.5 and `composer test` passes locally.
