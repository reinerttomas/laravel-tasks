---
paths:
    - 'app/**'
---

# App

## Prefer constructor/method injection over app()

Type-hint dependencies on controller actions, job `handle()` methods, and Livewire lifecycle hooks. Only reach for `app(X::class)` inside closures/static contexts where injection isn't reachable.

## No custom Events/Listeners

Decouple side effects via Eloquent Observers (`#[ObservedBy]`) and `dispatch(new Job(...))` (not `Job::dispatch()`, whose arguments PHPStan cannot type-check), not custom Event/Listener classes. Reserve `event()` for framework-native auth events only.

## Use helpers over facades

Use `auth()`/`config()` helper functions instead of `Auth::`/`Config::` facades; facades are the rare exception.

## Full-sentence translation keys

Call `__()` with the literal full English sentence as the key (no `lang/*.php` or `lang/*.json` files defined). Do not introduce short dot-notation keys for new project strings.

## Idempotent create writes

Use `relation()->firstOrCreate([...])` for get-or-create writes (e.g. likes, bookmarks, blocked accounts) instead of manual find-then-create checks.

## Named routes only for URLs

Generate URLs/redirects with `route()`/`to_route()` (named routes). Never use raw `url()` or `action([Controller::class, ...])`.

## Prefer static Str:: helpers

Use static `Str::` facade methods (e.g. `Str::limit`, `Str::startsWith`, `Str::contains`) for string manipulation rather than `Str::of()` fluent chains or native `str_*`/`strto*` functions.

## App defaults come from nunomaduro/essentials

`nunomaduro/essentials` configures framework defaults; do not re-declare them in AppServiceProvider. Change one by publishing and editing `config/essentials.php`. Active defaults:

- Strict models: lazy loading, silently discarded attributes, and missing attribute access throw.
- Relationships are automatically eager loaded across collections.
- `Http::preventStrayRequests()` and `Sleep::fake()` in tests, so fake every outgoing HTTP call.
- HTTPS is forced and destructive DB commands are prohibited in production.
- Production password defaults: 12–255 characters, mixed case, letters, numbers, symbols, uncompromised.
- Vite uses aggressive prefetching.

## CarbonImmutable date policy

Dates are immutable app-wide (essentials `ImmutableDates`). Use `now()`/`today()` helpers and typehint date properties/params as `CarbonImmutable`, not `Carbon`.
