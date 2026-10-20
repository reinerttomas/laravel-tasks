# 02: Dokploy setup and first deploy

**What to build:** The one-time setup only a human can do, then the first deploys through the new workflow, so the app runs on Dokploy. See `spec.md`.

**Blocked by:** 01

**Status:** ready-for-human

- [ ] `feat/docker` (with ticket 01) is merged into `main`, so GitHub offers the manual trigger.
- [ ] A Dokploy compose app points at this repository's `compose.yml`, with a domain routed to the `app` service on port 8080.
- [ ] The compose app's Environment holds `.env.dokploy` with every `…` filled in (`APP_KEY`, `APP_URL`, database and Redis passwords, `HORIZON_ALLOWED_EMAILS`, mail, Nightwatch token).
- [ ] GitHub environment `production` holds `DOKPLOY_URL`, `DOKPLOY_API_KEY` and `DOKPLOY_COMPOSE_ID`.
- [ ] After the first image push, the GHCR package `laravel-tasks` is public (or Dokploy has registry credentials).
- [ ] A deploy started from the Actions tab passes; GHCR shows `latest` on the same digest as the new `sha-…` tag; the app answers on its domain, login works and `/horizon` opens for a listed email.
- [ ] A second deploy moves `latest` and Dokploy runs the new image.
- [ ] Requests and Horizon jobs appear in Nightwatch (also closes the open criterion in `.scratch/docker/issues/04-nightwatch.md`).
