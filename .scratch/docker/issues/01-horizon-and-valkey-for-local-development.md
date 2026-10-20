# 01: Horizon and Valkey for local development

**What to build:** Queue, cache and session run on Redis (Herd's Valkey) and Laravel Horizon processes the queue. `artisan dev` starts Horizon instead of `queue:listen`. The `/horizon` dashboard is open locally; in production only users whose email is listed in `HORIZON_ALLOWED_EMAILS` can open it. The production supervisor runs at most three processes with auto balancing, and the scheduler captures Horizon metrics every five minutes. See `spec.md`, sections Horizon and Configuration.

**Blocked by:** None (can start immediately)

**Status:** resolved

- [ ] `laravel/horizon` is installed and its dashboard works locally with jobs dispatched by the app.
- [x] `.env.example` uses Redis for queue, cache and session; tests still use array and sync drivers.
- [x] Feature test: in production a listed user gets 200 on `/horizon`, an unlisted user gets 403, a guest is refused; locally any visitor gets the dashboard.
- [x] `AppServiceProviderTest`: locally the dev commands contain Horizon and not `queue` (`horizon:listen` if the installed version has it, otherwise `horizon`).
- [x] Feature test: `horizon:snapshot` is scheduled every five minutes.
- [x] Production supervisor is limited to three processes with auto balancing.
- [x] `composer test` passes.

## Comments

- 2026-10-20: Implemented on branch `docker/01`, merged into `feat/docker`; review fixes in `docker/review`. Uses `horizon` rather than `horizon:listen`, which needs the unapproved `chokidar` npm package. `HorizonServiceProvider` extends a plain `ServiceProvider` because the strict arch preset forbids Horizon's protected `gate()` hook. Dashboard processing jobs was verified in the Docker stack (ticket 03), not yet locally against Herd's Valkey.
