# 03: Compose stack with Postgres and Valkey

**What to build:** A `compose.yml` stack running `app`, `horizon`, `scheduler`, `ssr`, PostgreSQL 18 and Valkey 8 from the GHCR image tagged by `IMAGE_TAG`, configured from a single `.env`. Database credentials are taken from `DB_*`, Valkey is password-protected and persists to disk, no data service publishes ports, and storage lives in named volumes. Plus a `.env.dokploy` template modelled on matchday. See `spec.md`, sections Compose stack and Configuration.

**Blocked by:** 01, 02

**Status:** ready-for-agent

- [ ] With a locally built image tagged as `IMAGE_TAG`, `docker compose up` brings every service to healthy.
- [ ] `app` waits for healthy `postgres` and `valkey`, and runs migrations on start.
- [ ] Login works and a dispatched job is processed by the `horizon` service and visible in the dashboard.
- [ ] Data in PostgreSQL and queued jobs in Valkey survive `docker compose down` and `up`.
- [ ] PostgreSQL and Valkey are unreachable from the host; Valkey refuses unauthenticated clients.
- [ ] `.env.dokploy` lists every variable the stack needs, with a comment where the value is not obvious.
