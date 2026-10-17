---
paths:
    - 'app/Models/**'
---

# Models

## Models own data shape, not behavior

A model defines only columns, casts, relationships, scopes, and accessors/mutators. Every state change and domain operation, including small transitions like marking a document as indexed, lives in an Action under `app/Actions` (e.g. `MarkDocumentAsIndexed`); see `.ai/rules/actions.md`. Never call external services (HTTP, embedding generation, mail, job dispatching) from models, accessors, casts, or relationship methods.

## No static query methods

Do not add static methods that build or run queries (e.g. `Document::findSimilarTo($embedding)`, `Document::latestIndexed()`); models are not repositories. Compose queries outside the model with `Model::query()` and scopes.

## Final models

Declare every model `final`. Models are not extended; share behavior through traits or Actions.

## Configure metadata with Eloquent attributes

Use attributes from `Illuminate\Database\Eloquent\Attributes` instead of properties: `#[Fillable]`, `#[Hidden]`, `#[UseFactory]`, `#[UsePolicy]`, `#[ObservedBy]`, `#[Table]`. Never use `#[Unguarded]` or an empty `#[Guarded]`.

## UUID primary keys

Use `HasUuids` on every model for a UUIDv7 primary key (ADR 0003), and type IDs and foreign keys as `string`.

## Factory for every model

Create a factory with every model, bind it with `#[UseFactory(...)]`, and keep `use HasFactory` with a `@use HasFactory<XFactory>` generic. When a model has a policy, bind it with `#[UsePolicy(...)]`.

## Casts in the casts() method

Declare casts in `casts()`, not `$casts`. Cast enum columns to a PHP backed enum (the column is `string()`, see `.ai/rules/migrations.md`), dates to `datetime` (resolves to `CarbonImmutable` app-wide), and `vector` columns to `array`.

## Readonly model PHPDoc

Document every column and relationship with `@property-read`, including columns that application code changes. Type dates as `CarbonImmutable` and to-many relationships as `Illuminate\Database\Eloquent\Collection<int, X>`. The readonly docblock makes static analysis reject magic assignment, which enforces the next rule.

## No magic property assignment

Change state through `fill()` or `update()` inside an Action. Do not write `$model->attribute = ...; $model->save()`.

## Accessors and mutators via Attribute

Define accessors and mutators as a `protected` method returning `Attribute::make(get: ..., set: ...)`. Do not use `getXAttribute`/`setXAttribute`.

## Scopes via #[Scope]

Define local scopes as `protected` methods marked `#[Scope]` that take `Builder<self>` and return `void`. Do not use the `scopeX` prefix.

## Typed relationships, eager load at the call site

Type relationship methods with generics containing the related model and `$this`. Do not define `$with`; eager load explicitly with `with()`/`load()` where the data is used.

## Example

```php
/**
 * @property-read string $id
 * @property-read string $title
 * @property-read string $content
 * @property-read DocumentStatus $status
 * @property-read list<float>|null $embedding
 * @property-read CarbonImmutable|null $indexed_at
 * @property-read Collection<int, Chunk> $chunks
 */
#[Fillable(['title', 'content', 'status', 'embedding', 'indexed_at'])]
#[UseFactory(DocumentFactory::class)]
#[UsePolicy(DocumentPolicy::class)]
final class Document extends Model
{
    /** @use HasFactory<DocumentFactory> */
    use HasFactory, HasUuids;

    /**
     * @return HasMany<Chunk, $this>
     */
    public function chunks(): HasMany
    {
        return $this->hasMany(Chunk::class);
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function indexed(Builder $query): void
    {
        $query->where('status', DocumentStatus::Indexed);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => DocumentStatus::class,
            'embedding' => 'array',
            'indexed_at' => 'datetime',
        ];
    }
}
```
