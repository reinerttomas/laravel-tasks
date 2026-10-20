# 04: Nightwatch

**What to build:** The production stack reports errors, requests, queries, jobs and logs to Laravel Nightwatch through a `nightwatch-agent` service that receives only its token. App services send to the agent, the SSR service has Nightwatch disabled, logs go to `stderr` and Nightwatch, and secret request fields are redacted. See `spec.md`, sections Compose stack and Configuration.

**Blocked by:** 03

**Status:** resolved

- [x] `laravel/nightwatch` is installed.
- [x] The `nightwatch-agent` service is healthy and receives no variable other than `NIGHTWATCH_TOKEN`.
- [ ] With a real token, requests and Horizon jobs from the stack appear in Nightwatch; SSR health checks do not.
- [x] `.env.dokploy` sets the `stderr,nightwatch` log stack, payload capture and redaction of passwords, 2FA codes, tokens and passkey assertions.
- [x] `composer test` passes.

## Comments

- 2026-10-20: Implemented on branch `docker/04`, merged into `feat/docker`; review fixes in `docker/review` (stack starts without a token). Data reaching Nightwatch is unverified: no real token was available, the dummy token got a 401, though the agent log shows it received data from the app.
