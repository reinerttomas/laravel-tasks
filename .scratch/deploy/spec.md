# Manual deploy to Dokploy

Status: ready-for-agent

Deploy the Docker stack from `.scratch/docker/spec.md` to Dokploy with one manually triggered GitHub workflow. This is a trial project, so there are no releases, versions or changelog: a deploy builds the image from the chosen branch, pushes it to GHCR, moves the `latest` tag to it and asks Dokploy to redeploy. The workflow never writes the Dokploy env. Modelled on [reinerttomas/matchday](https://github.com/reinerttomas/matchday)'s deploy workflow and Dokploy deploy script, without its release-please pipeline and without its env rewrite.

## Problem Statement

The Compose stack expects an image from `ghcr.io/reinerttomas/laravel-tasks`, but nothing builds or publishes it. Deploying today means building on a laptop, pushing by hand and redeploying in Dokploy by hand.

Matchday tells Dokploy which image to run by rewriting `IMAGE_TAG` in the compose app's env. Dokploy's API has no way to change one variable: `compose.saveEnvironment` and `compose.update` replace the whole env string, and `compose.deploy` accepts no overrides. A read-modify-write of the entire production env on every deploy is the riskiest part of that pipeline, since a bad API response or a concurrent edit can wipe every secret.

## Solution

A `deploy` workflow started from the GitHub Actions tab (or `gh workflow run`). It runs the test suite, builds the production image from the selected branch, pushes it tagged with the commit, moves the `latest` tag onto that image, starts a Dokploy deploy and waits for its result. The Compose stack always runs `latest` and pulls it on every deploy, so Dokploy needs no per-deploy configuration and its env is only ever edited by a human.

## User Stories

1. As the developer, I want to deploy with one click in the Actions tab, so that I never build or push images by hand.
2. As the developer, I want to choose the branch to deploy, so that I can try a feature branch on the server before merging it.
3. As the developer, I want the full test suite to pass before an image is built, so that a broken commit never reaches the server.
4. As the developer, I want the tests to run on the same PHP version as the image, so that a version-specific bug cannot slip through.
5. As the developer, I want each image tagged with its commit, so that every build stays addressable after `latest` moves on.
6. As the developer, I want `latest` to move only once the image is built and pushed, and only right before Dokploy deploys it, so that a failed build never changes what a server restart would pull.
7. As the developer, I want the deploy never to write the Dokploy env, so that no deploy can lose or corrupt production secrets.
8. As the developer, I want the Compose stack to pull the image on every deploy, so that a moved `latest` is actually picked up.
9. As the developer, I want image builds cached between runs, so that deploys stay fast.
10. As the developer, I want the workflow to wait for Dokploy's result and fail when the deploy fails or times out, so that a green run means the app is live.
11. As the developer, I want a job summary with the commit tag, the digest now behind `latest` and the Dokploy status, so that I can tell exactly what is running without reading logs.
12. As the developer, I want the image to carry the commit as an OCI label, so that a running container can be traced back to its commit.
13. As the developer, I want two deploys never to overlap, so that a second click queues behind the first instead of racing it.
14. As the developer, I want Dokploy credentials kept in a GitHub environment and passed only to the step that needs them, so that other steps never see them.
15. As the developer, I want the Dokploy API key never printed or placed on a command line, so that it cannot leak through logs or the process list.
16. As the developer, I want a documented rollback that moves `latest` back to an earlier commit tag without rebuilding, so that I can recover quickly.
17. As the developer, I want written steps for the one-time Dokploy and GitHub setup, so that I can do it without guessing.

## Implementation Decisions

- **Deploy model:** a moving `latest` tag instead of a per-deploy `IMAGE_TAG`. Every app service in the Compose stack runs `ghcr.io/reinerttomas/laravel-tasks:latest` with `pull_policy: always`; `IMAGE_TAG` disappears from the Compose file and from `.env.dokploy`. This changes the Compose stack decided in `.scratch/docker/spec.md`.
- **Trigger:** `workflow_dispatch` only, no inputs; the branch picked in the dispatch UI is what gets built and deployed. GitHub only lists a dispatchable workflow once its file is on the default branch.
- **Jobs**, in order, each needing the previous:
    1. `ci`: the existing `tests` workflow, called through a new `workflow_call` trigger.
    2. `image`: build the Dockerfile's `deploy` target for `linux/amd64` with `VITE_APP_NAME=Laravel Tasks` (Vite inlines it at build time, so Dokploy's env cannot set it), push it to GHCR tagged `sha-<short commit>` only, with the standard OCI labels (including the revision), provenance annotations so GHCR links the package to the repo, and the GitHub Actions build cache. Outputs the `sha-…` tag and its digest.
    3. `deploy`: in the GitHub environment `production`, concurrency group `deploy-production` without cancelling a running deploy, 20-minute timeout. Points `latest` at the `sha-…` image by retagging it in the registry (no rebuild, no pull), runs the Dokploy deploy script with only the three Dokploy secrets in its env, and writes a job summary (commit tag, digest behind `latest`, Dokploy status and deployment id, or why it did not start).
- **Permissions:** workflow default is none; each job grants only what it needs (`contents: read`; `packages: write` for the image and deploy jobs, since the deploy job moves `latest`). Third-party actions are pinned to commit SHAs, matching the existing `tests` workflow.
- **Dokploy deploy script:** matchday's script without the env rewrite. It takes no arguments; it checks the three Dokploy env vars, the timeout and poll interval, and that `curl` and `jq` exist. It records the existing deployment ids, starts `compose.deploy` with a title naming the commit tag, finds the new deployment by title among ids it had not seen, polls until `done`, `error`, `cancelled` or a timeout, retries failed status reads until the deadline, and writes `deployment_id` and `status` to the step outputs. The API key travels only through a file descriptor. It never calls `compose.one`, `compose.saveEnvironment` or `compose.update`.
- **Secrets** (GitHub environment `production`): `DOKPLOY_URL`, `DOKPLOY_API_KEY`, `DOKPLOY_COMPOSE_ID`.
- **CI PHP version:** the `tests` workflow moves from PHP 8.4 to 8.5 to match the image.
- **GHCR visibility:** the repository is public; the package must be public too, or the Dokploy compose app needs registry credentials to pull.

## Testing Decisions

- No PHP code changes, so no new Pest tests. The `tests` workflow keeps running `composer test` unchanged apart from the PHP version.
- Workflows are checked with `actionlint`; the deploy script with `shellcheck`.
- The deploy script is exercised by hand against its guard rails: missing env vars, invalid timeout or poll interval values and missing tools each exit with the documented code before any API call.
- The deploy script's polling is exercised against a stub Dokploy API (a local HTTP server or a `curl` stub on `PATH`): a deployment that reaches `done`, one that reaches `error`, one that never appears (timeout), a status read that fails once and then succeeds, and an older deployment with the same title that must be ignored. A grep over the script confirms it calls no env-writing endpoint.
- The Compose stack still comes up healthy locally with a locally built image tagged `latest`.
- The whole chain is verified by the first real deploy from the Actions tab: tests pass, GHCR shows the `sha-…` tag with `latest` on the same digest, Dokploy runs it and the app answers on its domain; then a second deploy moves `latest` and Dokploy picks it up.

## Out of Scope

- Releases, versioning, changelog and release-please.
- Automatic deploys on push or merge.
- A rollback input on the workflow (rollback is the documented manual retag plus a deploy started in Dokploy).
- Multi-architecture images.
- PostgreSQL backups (still out of scope from the Docker spec).

## Further Notes

- Reference: matchday's `deploy.yml`, the image job of its `release.yml`, and `.infrastructure/dokploy-deploy.sh`.
- Rollback: retag an earlier `sha-…` image as `latest` in GHCR (for example with `docker buildx imagetools create`), then redeploy the compose app in Dokploy.
- Trade-off accepted: Dokploy itself no longer shows which commit runs; the workflow's job summary and the image's OCI revision label do.
- The Docker work lives on `feat/docker`; the workflow file has to reach `main` before GitHub offers the manual trigger.
