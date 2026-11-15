# User Collections (GH-15)

## To-do

Ordered in vertical slices. Each slice delivers one user-visible piece end-to-end; stop for review after each one.

| # | Slice | Step | Done |
|---|-------|------|------|
| 1 | Listing | Add `spatie/laravel-data` (add a `make composer` target if missing) | [ ] |
| 2 | Listing | Create `App\Concerns\HasUlid` trait | [ ] |
| 3 | Listing | Migration `create_collections_table`, `Collection` model, `User::collections()`, `CollectionFactory` | [ ] |
| 4 | Listing | `CollectionData` DTO (no `id`) | [ ] |
| 5 | Listing | `CollectionRepository::listForUser`, `CollectionService::list` | [ ] |
| 6 | Listing | `CollectionController@index` + `Route::resource(...)->except('show')` (only `index` wired so far) | [ ] |
| 7 | Listing | `pages/collections/index.tsx` (list + empty state), sidebar nav item | [ ] |
| 8 | Listing | Tests: guest redirect, own-collections-only, no `id` in props, relation unit tests | [ ] |
| — | **Review checkpoint** | | |
| 9 | Create | `StoreCollectionData`, `StoreCollectionRequest` (unique per user, `withoutTrashed`) | [ ] |
| 10 | Create | `CollectionRepository::create`, `CollectionService::create`, `create`/`store` actions | [ ] |
| 11 | Create | `pages/collections/create.tsx` + shared `pages/collections/collection-form.tsx` | [ ] |
| 12 | Create | Tests: row count, active by default, `assertInvalid` cases, duplicate allowed for another user / after soft delete, guest redirect | [ ] |
| — | **Review checkpoint** | | |
| 13 | Edit | `CollectionPolicy::update` | [ ] |
| 14 | Edit | `UpdateCollectionData`, `UpdateCollectionRequest` (unique ignores self) | [ ] |
| 15 | Edit | `CollectionRepository::update`, `CollectionService::update`, `edit`/`update` actions | [ ] |
| 16 | Edit | `pages/collections/edit.tsx` (reuses `collection-form.tsx`), edit link on index | [ ] |
| 17 | Edit | Tests: update persists, keeping own name passes, 403 non-owner, `assertInvalid`, guest redirect | [ ] |
| — | **Review checkpoint** | | |
| 18 | Toggle active | `ToggleCollectionActiveController` (invokable) + `PATCH collections/{collection}/toggle-active` route | [ ] |
| 19 | Toggle active | `CollectionRepository::setActive`, `CollectionService::toggleActive` | [ ] |
| 20 | Toggle active | Active badge + toggle control on index | [ ] |
| 21 | Toggle active | Tests: flips both ways, 403 non-owner, guest redirect | [ ] |
| — | **Review checkpoint** | | |
| 22 | Delete | `CollectionPolicy::delete`, `destroy` action, `CollectionRepository::delete`, `CollectionService::delete` | [ ] |
| 23 | Delete | `CollectionDeleted` event, `QueueCollectionCleanup` listener, `PurgeCollection` job, `CollectionRepository::findTrashed` / `forceDelete` | [ ] |
| 24 | Delete | Generic `components/confirm-delete-dialog.tsx`, delete action on index | [ ] |
| 25 | Delete | Tests: `assertSoftDeleted`, event dispatched, 403 non-owner, guest redirect, job force-deletes trashed / skips restored | [ ] |

## Context

Users of the RAG app need a way to group the documents they will upload into named **collections**. A collection can later be queried as a unit, so users need to switch it on or off without deleting it. Deleting a collection must eventually remove its documents, chunks and embeddings. Those don't exist yet, so this ticket raises an event and queues a cleanup job that the document feature will extend.

Acceptance criteria:
- As a user, I can create a collection with a name and an optional description.
- As a user, I can see the collections I have created.
- As an owner, I can activate or deactivate a collection.
- As an owner, I can edit a collection.
- As an owner, I can delete a collection.

This is the first domain feature in `backend/`. `docs/architecture/index.md` assumes two foundations that don't exist yet: `spatie/laravel-data` for DTOs and the `App\Concerns\HasUlid` trait. This ticket adds both.

## Decisions

1. **Activate/deactivate is a single-action invokable controller** at `PATCH /collections/{collection}/toggle-active` (`collections.toggle-active`). The architecture doc says not to add ad-hoc actions to a resource controller without agreement. A dedicated invokable controller keeps `CollectionController` purely CRUDY and keeps edit separate from the state change.
2. **Soft delete, then event → listener → queued job.** The architecture rule says the job reloads data at execution time. That only works for a deletion event if the row is still loadable, so `Collection` uses `SoftDeletes`. The job reloads with `withTrashed()`. If the row is missing or was restored (`deleted_at` is null), it bails. Otherwise it purges, which for now just means force-deleting the row; documents and chunks get purged later. The event payload is just `int $collectionId`.
3. **Names are unique per user and ignore soft-deleted rows.** This is enforced in the FormRequests with `Rule::unique('collections')->where('user_id', …)->withoutTrashed()`. There is no DB-level unique index, because a plain `(user_id, name)` index would block reusing a name while a deleted collection waits for its purge job.
4. **New collections are active by default** (`is_active` boolean, default `true`). Active/inactive is a binary flag, not a categorical set, so a boolean is enough; an enum isn't needed.
5. **Public ID is the ULID.** The model uses `HasUlid` and route binding resolves on `ulid`. `CollectionData` leaves out `id`. The internal id only appears in the event/job payload, which never reaches the client.
6. **Ownership is enforced by `CollectionPolicy`.** `update`, `toggle` and `delete` (edit reuses `update`) return `$user->id === $collection->user_id`. Routes apply it with `->can(...)` middleware, so non-owners get a 403 before reaching the controller. `index` is scoped by the repository to the authenticated user.
7. **No `show` action yet.** A collection's detail page makes sense once documents exist, so the resource is registered with `->except('show')`.
8. **Layering follows the architecture doc**: Controller → Service → Repository, with DTOs between layers. The controller receives the route-bound model only so the policy and the service know which record is meant. All reads and writes go through `CollectionRepository`.

## Implementation

### Foundations

`app/Concerns/HasUlid.php`:

```php
<?php

namespace App\Concerns;

use Illuminate\Database\Eloquent\Concerns\HasUlids;

trait HasUlid
{
    use HasUlids;

    public function initializeHasUlid(): void
    {
        if (! in_array('ulid', $this->fillable, true)) {
            $this->mergeFillable(['ulid']);
        }
    }

    /**
     * @return array<int, string>
     */
    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }
}
```

`HasUlids` fills the columns in `uniqueIds()` on `creating` when they're empty. Because `uniqueIds()` doesn't include `id`, the bigint auto-increment PK is untouched.

### Migration

```php
Schema::create('collections', function (Blueprint $table) {
    $table->id();
    $table->ulid()->unique();
    $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();
    $table->string('name');
    $table->text('description')->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamps();
    $table->softDeletes();

    $table->index(['user_id', 'deleted_at']);
});
```

### Model

```php
class Collection extends Model
{
    /** @use HasFactory<CollectionFactory> */
    use HasFactory, HasUlid, SoftDeletes;

    protected $fillable = ['name', 'description', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

`User` gains `collections(): HasMany`. `CollectionFactory` has an `inactive()` state.

### DTOs (`app/Data/Collections/`)

```php
class CollectionData extends Data
{
    public function __construct(
        public string $ulid,
        public string $name,
        public ?string $description,
        public bool $is_active,
        public CarbonImmutable $created_at,
    ) {}
}

class StoreCollectionData extends Data
{
    public function __construct(
        public string $name,
        public ?string $description,
    ) {}
}

class UpdateCollectionData extends Data
{
    public function __construct(
        public string $name,
        public ?string $description,
    ) {}
}
```

### Repository (`app/Repositories/CollectionRepository.php`)

| Method | Purpose |
|--------|---------|
| `listForUser(int $userId): DataCollection<CollectionData>` | User's non-deleted collections, latest first |
| `create(int $userId, StoreCollectionData $data): CollectionData` | Insert |
| `update(Collection $collection, UpdateCollectionData $data): CollectionData` | Update name/description |
| `setActive(Collection $collection, bool $active): CollectionData` | Set flag |
| `delete(Collection $collection): void` | Soft delete |
| `findTrashed(int $id): ?Collection` | `withTrashed()->find($id)`, used by the purge job |
| `forceDelete(Collection $collection): void` | Permanent removal |

### Service (`app/Services/CollectionService.php`)

- `list(int $userId)`, `create(int $userId, StoreCollectionData $data)` and `update(Collection, UpdateCollectionData)` delegate to the repository.
- `toggleActive(Collection $collection)` flips the flag:
  ```php
  $active = ! $collection->is_active;

  return $this->collections->setActive($collection, $active);
  ```
- `delete(Collection $collection)`:
  ```php
  $collectionId = $collection->id;
  $this->collections->delete($collection);
  CollectionDeleted::dispatch($collectionId);
  ```
- `purge(int $collectionId)` is called by the job. It loads via `findTrashed`, returns if the row is null or `! $collection->trashed()`, and otherwise force-deletes it. A `// TODO(documents)` marks where the document/chunk purge goes.

### Requests

`StoreCollectionRequest`:

```php
'name' => [
    'required', 'string', 'max:255',
    Rule::unique('collections')
        ->where('user_id', $this->user()->id)
        ->withoutTrashed(),
],
'description' => ['nullable', 'string', 'max:2000'],
```

`UpdateCollectionRequest` uses the same rules, adding `->ignore($this->route('collection')->id)` on the unique rule.

### Policy (`app/Policies/CollectionPolicy.php`)

`update`, `toggle` and `delete` all return `$user->id === $collection->user_id`. The policy is auto-discovered.

### Routes (`routes/web.php`, inside the `auth, verified` group)

```php
Route::resource('collections', CollectionController::class)
    ->except('show')
    ->middlewareFor(['edit', 'update'], 'can:update,collection')
    ->middlewareFor('destroy', 'can:delete,collection');

Route::patch('collections/{collection}/toggle-active', ToggleCollectionActiveController::class)
    ->middleware('can:toggle,collection')
    ->name('collections.toggle-active');
```

### Controllers

- `CollectionController`:
  - `index` renders `collections/index` with `collections` as a list of `CollectionData`.
  - `create` renders `collections/create`.
  - `store` builds `StoreCollectionData::from($request->validated())`, calls the service, and redirects to `collections.index`.
  - `edit` renders `collections/edit` with `collection` as `CollectionData`.
  - `update` works like `store`.
  - `destroy` calls the service and redirects to the index.
- `ToggleCollectionActiveController::__invoke` calls `toggleActive`, then redirects back.

Following the code-style rule, every DTO hydration gets its own named variable before it's passed on.

### Events & jobs

- `app/Events/CollectionDeleted.php`: `public function __construct(public int $collectionId) {}`.
- `app/Listeners/QueueCollectionCleanup.php`: `PurgeCollection::dispatch($event->collectionId)`.
- `app/Jobs/PurgeCollection.php` (`ShouldQueue`): `handle(CollectionService $service)` calls `$service->purge($this->collectionId)`.

### Frontend (`resources/js/pages/collections/`)

- `index.tsx`:
  - Header with a "New collection" button.
  - Card or table rows showing name, description and an Active/Inactive `Badge`.
  - Per-row actions: Edit link, activate/deactivate button (`router.patch(toggleActive(ulid))`), and Delete, which opens `ConfirmDeleteDialog` and then calls `router.delete(destroy(ulid))`.
  - Empty state when there are no collections.
  - Breadcrumbs through `.layout`, like `dashboard.tsx`.
- `collection-form.tsx`: `name` input and `description` textarea with `InputError`. Used by both create and edit with Inertia `<Form {...store.form()}>` / `<Form {...update.form(ulid)}>`.
- `create.tsx`, `edit.tsx`: thin wrappers around the form.
- `resources/js/components/confirm-delete-dialog.tsx`: a generic confirm dialog built on `ui/dialog.tsx`, taking title, description, `onConfirm` and `processing`.
- `components/app-sidebar.tsx`: add a "Collections" item to `mainNavItems` (lucide `Library` icon).
- Imports come from the Wayfinder routes in `@/routes/collections`, not from `@/actions`.
- `types/`: a `Collection` type matching `CollectionData`.

## Files to create/modify

| File | Action |
|------|--------|
| `Makefile` | Add `composer` target if missing |
| `backend/composer.json` | Add `spatie/laravel-data` |
| `backend/app/Concerns/HasUlid.php` | Create |
| `backend/database/migrations/xxxx_create_collections_table.php` | Create |
| `backend/app/Models/Collection.php` | Create |
| `backend/app/Models/User.php` | Add `collections()` |
| `backend/database/factories/CollectionFactory.php` | Create |
| `backend/app/Data/Collections/{CollectionData,StoreCollectionData,UpdateCollectionData}.php` | Create |
| `backend/app/Repositories/CollectionRepository.php` | Create |
| `backend/app/Services/CollectionService.php` | Create |
| `backend/app/Policies/CollectionPolicy.php` | Create |
| `backend/app/Http/Requests/Collections/{StoreCollectionRequest,UpdateCollectionRequest}.php` | Create |
| `backend/app/Http/Controllers/CollectionController.php` | Create |
| `backend/app/Http/Controllers/ToggleCollectionActiveController.php` | Create |
| `backend/app/Events/CollectionDeleted.php` | Create |
| `backend/app/Listeners/QueueCollectionCleanup.php` | Create |
| `backend/app/Jobs/PurgeCollection.php` | Create |
| `backend/routes/web.php` | Add routes |
| `backend/resources/js/pages/collections/{index,create,edit,collection-form}.tsx` | Create |
| `backend/resources/js/components/confirm-delete-dialog.tsx` | Create |
| `backend/resources/js/components/app-sidebar.tsx` | Add nav item |
| `backend/resources/js/types/index.d.ts` (or equivalent) | Add `Collection` type |
| `backend/tests/Feature/Collections/*Test.php` | Create |
| `backend/tests/Unit/Models/CollectionTest.php`, `UserTest.php` | Create |

## Testing plan

**Feature** (`tests/Feature/Collections/`):
- `IndexCollectionTest`:
  - A guest is redirected to login.
  - A user sees only their own collections, not soft-deleted ones and not another user's.
  - The props contain `ulid` and no `id`.
- `StoreCollectionTest`:
  - A guest is redirected.
  - Creating a collection adds one row (`assertDatabaseCount`) with `is_active = true`.
  - The description is optional.
  - `assertInvalid` for: missing name, name over 255, description over 2000, duplicate name for the same user.
  - The same name is allowed for a different user.
  - The same name is allowed after the original is soft-deleted.
  - The `create` page renders.
- `UpdateCollectionTest`:
  - A guest is redirected.
  - The owner can update, and the edit page renders.
  - Saving with an unchanged name passes.
  - Renaming to another of your own collection names fails.
  - A non-owner gets 403 on both edit and update.
  - `assertInvalid` for a missing name.
- `ToggleCollectionActiveTest`:
  - A guest is redirected.
  - Toggling flips active → inactive, and inactive → active.
  - A non-owner gets 403.
- `DestroyCollectionTest`:
  - A guest is redirected.
  - The owner's delete soft-deletes the row (`assertSoftDeleted`) and dispatches `CollectionDeleted` with the right id (`Event::fake`).
  - A non-owner gets 403 and the row is untouched.
- `PurgeCollectionTest`:
  - The job force-deletes a trashed collection (`assertDatabaseMissing`).
  - The job leaves a restored collection alone.
  - The job doesn't fail for an id that doesn't exist.
  - The listener queues `PurgeCollection` (`Queue::fake`).

**Unit**:
- `Collection::user()` is an instance of `BelongsTo`.
- `User::collections()` is an instance of `HasMany`.

## Verification

1. `make test ARGS="--filter=Collection"` passes.
2. `make check` passes (all tests and Larastan), and `make lint` is clean.
3. `make artisan ARGS="wayfinder:generate"` produces `resources/js/routes/collections`.
4. Manual check at http://localhost:8000/collections:
   - Create a collection.
   - Try a duplicate name and see the validation error.
   - Edit it.
   - Toggle it and watch the badge change.
   - Delete it through the confirm dialog.
5. `make logs ARGS=queue` shows `PurgeCollection` processed, and the row is gone from `collections` (check in pgAdmin).
