# 02: Production image with Octane

**What to build:** A multi-stage production image, modelled on matchday, that serves the app with Octane on FrankenPHP (PHP 8.5). The frontend and SSR bundle are built with bun 1.3.14, `VITE_APP_NAME` defaults to "Laravel Tasks", and a `.dockerignore` adapted from matchday keeps dev files, tests, docs, tickets and agent config out of the image. See `spec.md`, section Image.

**Blocked by:** None (can start immediately)

**Status:** resolved

- [x] `laravel/octane` is installed with FrankenPHP as the server.
- [x] `docker build` succeeds; the Composer install layer stays cached when only application code changes.
- [x] The image contains no dev dependencies, tests, docs, `.scratch` or agent config.
- [x] A container started from the image alone (with an `APP_KEY`) becomes healthy through `healthcheck-octane` and answers on port 8080.
- [x] `pdo_pgsql` and `redis` extensions are present in the image.
- [x] `composer test` passes.

## Comments

- 2026-10-20: Implemented on branch `docker/02`, merged into `feat/docker`. Pull `serversideup/php:8.5-frankenphp` before building: an older cached base ships FrankenPHP below 1.13 and fails Octane's version check.
