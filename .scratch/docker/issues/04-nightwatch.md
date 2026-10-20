# 04: Nightwatch

**What to build:** The production stack reports errors, requests, queries, jobs and logs to Laravel Nightwatch through a `nightwatch-agent` service that receives only its token. App services send to the agent, the SSR service has Nightwatch disabled, logs go to `stderr` and Nightwatch, and secret request fields are redacted. See `spec.md`, sections Compose stack and Configuration.

**Blocked by:** 03

**Status:** ready-for-agent

- [ ] `laravel/nightwatch` is installed.
- [ ] The `nightwatch-agent` service is healthy and receives no variable other than `NIGHTWATCH_TOKEN`.
- [ ] With a real token, requests and Horizon jobs from the stack appear in Nightwatch; SSR health checks do not.
- [ ] `.env.dokploy` sets the `stderr,nightwatch` log stack, payload capture and redaction of passwords, 2FA codes, tokens and passkey assertions.
- [ ] `composer test` passes.
