# 01: Horizon and Valkey for local development

**What to build:** Queue, cache and session run on Redis (Herd's Valkey) and Laravel Horizon processes the queue. `artisan dev` starts Horizon instead of `queue:listen`. The `/horizon` dashboard is open locally; in production only users whose email is listed in `HORIZON_ALLOWED_EMAILS` can open it. The production supervisor runs at most three processes with auto balancing, and the scheduler captures Horizon metrics every five minutes. See `spec.md`, sections Horizon and Configuration.

**Blocked by:** None (can start immediately)

**Status:** ready-for-agent

- [ ] `laravel/horizon` is installed and its dashboard works locally with jobs dispatched by the app.
- [ ] `.env.example` uses Redis for queue, cache and session; tests still use array and sync drivers.
- [ ] Feature test: in production a listed user gets 200 on `/horizon`, an unlisted user gets 403, a guest is refused; locally any visitor gets the dashboard.
- [ ] `AppServiceProviderTest`: locally the dev commands contain Horizon and not `queue` (`horizon:listen` if the installed version has it, otherwise `horizon`).
- [ ] Feature test: `horizon:snapshot` is scheduled every five minutes.
- [ ] Production supervisor is limited to three processes with auto balancing.
- [ ] `composer test` passes.
