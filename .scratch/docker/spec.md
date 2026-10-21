# Docker deployment

Status: resolved

Package the application as a production Docker image and a Compose stack for Dokploy, following the setup of [reinerttomas/matchday](https://github.com/reinerttomas/matchday), with PostgreSQL instead of SQLite, Valkey for queue, cache and session, Laravel Horizon for the queue and Laravel Nightwatch for monitoring.

## Problem Statement

The application only runs locally under Laravel Herd. There is no way to deploy it: no image, no service definitions, no production configuration. The sibling project matchday already has a proven Docker and Dokploy setup, but it is built around SQLite, pnpm and a plain queue worker, while this project uses PostgreSQL and bun and should try Redis-compatible infrastructure with Horizon.

## Solution

A multi-stage production image running the app on Octane with FrankenPHP, and a Compose stack that runs the app, the Horizon queue worker, the scheduler, the Inertia SSR server, PostgreSQL, Valkey and the Nightwatch agent, configured from a single `.env` that Dokploy writes. Local development moves to the same drivers (Valkey in Herd, Horizon in the dev command), so local behaviour matches production.

## User Stories

1. As the developer, I want a production image built from the repository, so that the application can be deployed anywhere Docker runs.
2. As the developer, I want the image to install Composer dependencies in a cached layer, so that rebuilds without dependency changes are fast.
3. As the developer, I want the frontend and SSR bundle built with bun at the same version as CI, so that the image matches what tests run against.
4. As the developer, I want the image to carry no dev dependencies, tests, docs, tickets or agent config, so that it is small and leaks nothing.
5. As the developer, I want the app served by Octane on FrankenPHP, so that requests are fast and the setup matches matchday.
6. As the developer, I want each container to report its health, so that Compose and Dokploy only route traffic and start dependants when a service is ready.
7. As the developer, I want PostgreSQL running inside the stack with persistent storage, so that one `docker compose up` brings up a working application.
8. As the developer, I want the database credentials entered once in `.env`, so that the database container and the app cannot disagree.
9. As the developer, I want migrations to run automatically when the app container starts, so that a deploy needs no manual step.
10. As the developer, I want the app to wait until PostgreSQL and Valkey are healthy, so that startup migrations never race the database.
11. As the developer, I want queue, cache and session stored in Valkey, so that I can learn a Redis-compatible setup and take load off the database.
12. As the developer, I want Valkey to persist its data to disk, so that queued jobs survive a container restart.
13. As the developer, I want Valkey protected by a password, so that nothing else on the host can read or enqueue jobs.
14. As the developer, I want neither PostgreSQL nor Valkey exposed outside the Compose network, so that they are reachable only by the app.
15. As the developer, I want Horizon to process the queue in production, so that I can see throughput, failures and wait times in its dashboard.
16. As the developer, I want Horizon limited to three processes in production, so that it fits a small VPS alongside the database and Octane.
17. As the developer, I want Horizon metrics captured every five minutes by the scheduler, so that the dashboard shows throughput and runtime charts.
18. As the developer, I want only the email addresses I list in an environment variable to open the Horizon dashboard in production, so that access is controlled without code changes.
19. As a signed-in user whose email is not on the Horizon list, I want to be refused the dashboard, so that queue internals stay private.
20. As the developer, I want the dashboard open to anyone locally, so that I can inspect jobs during development without configuration.
21. As the developer, I want `artisan dev` to run Horizon instead of `queue:listen`, so that local queue processing works like production.
22. As the developer, I want the local `.env.example` to use Redis for queue, cache and session, so that a fresh checkout uses Herd's Valkey like production does.
23. As the developer, I want tests to keep running on array and sync drivers, so that CI needs no Redis service.
24. As the developer, I want the scheduler running as its own service, so that scheduled commands run without cron on the host.
25. As the developer, I want the Inertia SSR server running as its own service, so that pages are server-rendered in production.
26. As the developer, I want uploaded files kept in named volumes, so that they survive redeploys.
27. As the developer, I want errors, requests, queries and logs reported to Nightwatch, so that I can monitor production.
28. As the developer, I want the Nightwatch agent to receive only its token, so that a third-party image never sees the app's other secrets.
29. As the developer, I want the SSR service excluded from Nightwatch, so that its health checks do not pollute monitoring.
30. As the developer, I want secret request fields redacted from Nightwatch payloads, so that passwords, 2FA codes and tokens are never stored there.
31. As the developer, I want a documented `.env.dokploy` template, so that setting up the Dokploy app is a copy, paste and fill-in exercise.
32. As the developer, I want the Compose stack to run a tagged image from GHCR, so that deploys are reproducible and match matchday once the release workflow exists.

## Implementation Decisions

### Image

- Base image `serversideup/php:8.5-frankenphp`, adding the `intl` and `bcmath` extensions. `pdo_pgsql` and `redis` are part of the base image; confirm while building.
- Same stages as matchday: `base`, `composer` (install from `composer.json` and `composer.lock` first, then copy the code and dump an optimized autoloader, all without dev dependencies or scripts), `frontend`, `deploy`.
- The `frontend` stage uses bun 1.3.14 (the CI version), copied from the official bun image, with a frozen-lockfile install and the `build:ssr` script. It needs the `composer` stage output because the Wayfinder Vite plugin runs Artisan. `VITE_APP_NAME` defaults to "Laravel Tasks".
- The `deploy` stage copies Node for the SSR server, the application, the built assets and the SSR bundle, runs as `www-data`, starts Octane on FrankenPHP (port 8080, max 500 requests per worker) and uses the `healthcheck-octane` health check. The SQLite directory from matchday is dropped.
- New dependencies: `laravel/octane`, `laravel/horizon`, `laravel/nightwatch` (approved).
- `.dockerignore` follows matchday, adapted to this repo (bun instead of pnpm, `GLOSSARY.md` instead of `CONTEXT.md`, the `.superset`, `.githooks` and `.junie` directories).

### Compose stack

- The Compose file is named `compose.yml`, matching matchday.
- App services share one anchor: the GHCR image `ghcr.io/reinerttomas/laravel-tasks` with the tag taken from `IMAGE_TAG` (required), `env_file: .env`, restart unless stopped, and the private and public storage volumes. Only values that follow from the stack's own services go in `environment`: database connection and host, Redis host, the Redis drivers for queue, cache and session, the SSR URL and the Nightwatch ingest URI. Everything else comes from `.env`.
- `app` has autorun enabled (migrations, optimize, storage link) and depends on `postgres` and `valkey` being healthy and `ssr` being started.
- `horizon` replaces matchday's `queue` service: runs Horizon, stops on SIGTERM, uses the `healthcheck-horizon` health check and starts once `app` is healthy.
- `scheduler` and `ssr` behave as in matchday. `ssr` talks to itself on localhost, has Nightwatch disabled and mounts no volumes.
- `postgres` uses `postgres:18-alpine` with a named volume mounted at `/var/lib/postgresql` (the mount point PostgreSQL 18 expects), takes its database, user and password from `DB_DATABASE`, `DB_USERNAME` and `DB_PASSWORD`, and has a `pg_isready` health check.
- `valkey` uses `valkey/valkey:8-alpine` with a named volume, append-only persistence, a password from `REDIS_PASSWORD`, and a health check that authenticates.
- No service except the app's HTTP port is published; Dokploy's Traefik routes to the app.
- `nightwatch-agent` uses `laravelphp/nightwatch-agent:v1`, receives only `NIGHTWATCH_TOKEN` and has the same health check as in matchday.

### Horizon

- Production supervisor: at most three processes, auto balancing.
- The `viewHorizon` gate allows users whose email appears in the comma-separated `HORIZON_ALLOWED_EMAILS`; Horizon itself keeps the dashboard open in the local environment.
- The scheduler runs `horizon:snapshot` every five minutes.
- `artisan dev` drops the default `queue` process and registers Horizon instead, using `horizon:listen` (restarts on code changes) if the installed Horizon version provides it, otherwise `horizon`. The existing rule that skips the PHP server locally stays.

### Configuration

- `.env.example` switches queue, cache and session to Redis; the existing `REDIS_*` values already point at Herd's Valkey. The developer updates their own `.env`.
- `.env.dokploy` follows matchday: image tag, app name "Laravel Tasks", production settings, opcache on and SSL off (Traefik terminates TLS), database credentials, Redis password, `HORIZON_ALLOWED_EMAILS`, the log stack `stderr,nightwatch`, Nightwatch token, payload capture and redaction of secret fields. Matchday's Google OAuth and SQLite busy timeout are dropped; mail settings follow what this project actually uses.

## Testing Decisions

- Tests exercise external behaviour through the highest available seam: HTTP responses, the registered dev commands and the schedule. They do not call gates or read config directly.
- **Horizon dashboard access** (new feature test): in the production environment a signed-in user whose email is listed gets 200, a signed-in user who is not listed gets 403, and a guest is refused. In the local environment any visitor gets the dashboard.
- **Dev processes** (extends the existing `AppServiceProviderTest`): locally the dev command list contains Horizon and no longer contains `queue`. The existing expectation that it contains `queue` changes accordingly.
- **Schedule** (new feature test): `horizon:snapshot` is scheduled every five minutes.
- Tests keep the array and sync drivers from `phpunit.xml`, so no Redis is needed in CI.
- The image and the Compose stack have no automated tests (CI does not build Docker). They are verified by hand in each ticket's acceptance criteria: the image builds, a standalone container is healthy, and in the full stack every service is healthy, migrations run, login works and a job passes through Horizon.

## Out of Scope

- GitHub workflows that build and push the image to GHCR and trigger a Dokploy deploy (matchday's release and deploy workflows and its Dokploy deploy script). Until they exist, the stack runs only with an image built and tagged by hand.
- PostgreSQL backups. A database inside Compose does not get the automatic backups Dokploy offers for its own database services.
- Horizon notifications (Slack, mail) for long wait times.

## Further Notes

- Reference setup: matchday's Dockerfile, Compose file, `.dockerignore` and `.env.dokploy`.
- Run `composer test` before every commit, as `AGENTS.md` requires; Octane, Horizon and Nightwatch may add files that PHPStan, Pint or type coverage need to accept.
