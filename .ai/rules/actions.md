---
paths:
    - 'app/Actions/**'
---

# Actions

## Single-purpose Action classes

Put domain operations in final readonly Action classes directly under app/Actions. Keep the folder flat: no domain sub-folders. Actions hold writes and also read steps such as retrieval (e.g. `RetrievePassages`) or pure transformations (e.g. `SplitScreenplay`). Inject stable collaborators through the constructor, and pass request-time models and values to `handle()`. Resolve actions through Laravel's container in controllers, console commands, observers. No Action suffix and no base class. Do not use auth(), session(), or request(); pass models and values in.

## Generate actions with make:action

Create actions with `php artisan make:action {Name} --no-interaction` (from nunomaduro/essentials). The generator always appends an `Action` suffix, so rename the file and class to drop it right after generating.

## Services wrap external sites and APIs

A client for an external site or API (e.g. `Imsdb`) lives in app/Services, not in app/Actions. Actions inject the service and call it; the service owns the HTTP calls and turns failures into descriptive exceptions.

## Inject actions and pass runtime data to handle

Resolve action classes through Laravel's container. Inject stable collaborators through the constructor; pass request-time models and validated values to handle(). Controllers, observers, console commands should receive actions through dependency injection where supported.

## Use CRUD names for resource mutations

Prefer Create{Resource}, Update{Resource}, and Delete{Resource} for resource mutations. Keep a domain-specific verb only when the operation is not naturally CRUD.
