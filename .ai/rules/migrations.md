---
paths:
    - 'database/migrations/**'
---

# Migrations

## Foreign key definitions

Use `$table->foreignIdFor(Model::class)->constrained()` for foreign keys; it creates a `uuid` column for `HasUuids` models (every model, ADR 0003). Pass the column name for non-conventional names: `foreignIdFor(User::class, 'author_id')`. Do not add `cascadeOnDelete()`; deleting a parent must not silently wipe its history, so delete dependent rows explicitly in the Action. Only fall back to manual `$table->foreign()->references()->on()` when the referenced column is not the model's primary key.

## One-way migrations

Do not implement `down()` reverse logic; migrations are one-way (omit the `down()` method).

## Enum column storage

Store enum-backed columns as `string()` in migrations; cast to a PHP backed enum via the model's `casts()` method rather than using a DB `->enum()` column.
