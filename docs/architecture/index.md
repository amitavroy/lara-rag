# Architecture

## Layering

- **Repositories** are the only place queries happen. All Eloquent/DB querying lives in repository classes.
- **Services** contain the business logic — modifications, checks, orchestration. Services call repositories (via DI) to read and write data.
- **Controllers** call services (via DI) to get data and perform actions. Controllers never call repositories directly.
- **Models** are never called directly from Controllers or Services for data access — all data access goes through a Repository.

## Data Transfer Objects (DTOs)

- DTOs are the standard shape for data moving between layers. Prefer passing and returning DTOs over raw arrays or Eloquent models.
- **Repositories** return DTOs (or collections of DTOs) to services, not raw Eloquent models or query results.
- **Services** return DTOs to controllers, reflecting the outcome of business logic in a well-typed shape.
- **Responses** sent from Controllers should be built from DTOs, so the response shape is explicit and easy to trace back to its source.
- DTOs are implemented using [`spatie/laravel-data`](https://spatie.be/docs/laravel-data). Use its `Data` classes rather than plain arrays, `stdClass`, or hand-rolled readonly classes, so consumers get validation, transformation, and IDE/static-analysis support for free.

## Events & Queued Jobs

- **Event payloads must be as small as possible — normally just the record's id.** Never put an Eloquent model, or a DTO snapshot of the full record, in an event. Example: `TimeEntryDeleted` takes `int $timeEntryId`, nothing more.
- The flow is **event → listener → queued job**. The listener's job is to hand the id to a job; the **job reloads the data it needs at execution time**.
- This reload is the point of the rule, not an inefficiency to optimise away: a job can sit in the queue for a long time, and the record may have changed or become irrelevant in the meantime. Reloading lets the job **validate the data is still correct before acting on it**, and bail out if it isn't. A snapshot captured at dispatch time cannot do that.
- Keeping payloads to ids also keeps the serialized job body small and avoids stale/unserializable state on the queue.
- Note the consequence for deletion events: if the job must read the deleted row itself, the record has to remain loadable (e.g. soft deletes). Decide that per event — do not work around it by fattening the payload.

## Controllers: CRUDY design

- This is an Inertia.js application: `create` and `edit` are real, Inertia-rendered form pages, not just API endpoints. Controllers are built CRUDY-style with the full set of resource verbs — `index`, `show`, `create`, `store`, `edit`, `update`, `destroy` — registered via `Route::resource()`, plus conventional extras like the `Y` (e.g. an `import`/`export`-style action) when it's genuinely resourceful.
- Do not use `Route::apiResource()`/`apiResources()` for these controllers — it drops `create`/`edit`, which this app needs for its Inertia form pages.
- Do not add ad-hoc, non-standard actions to a resource controller (e.g. `POST /orders/{order}/approve`) without discussing it first. Flag the action and its intended route/verb before implementing, so we can agree on whether it belongs on the existing controller, a dedicated single-action controller, or a nested resource.

## Primary Keys & Public Identifiers

- All entities use a `bigint` auto-incrementing `id` as their primary key. This is the key used for foreign key relationships between tables.
- Entities that are public-facing (exposed in URLs or to external consumers — e.g. `Client`, `Project`, `Summary`) additionally have a `ulid` column, used for public-facing lookups instead of exposing the internal `id`.
- A ULID is globally unique by construction, so no per-tenant/company uniqueness scoping is needed once a `Company` (or equivalent tenant) concept exists — unlike a human-readable slug, a ULID column only ever needs a plain unique constraint on `ulid` itself.
- **Hard rule: the internal `id` (PK) must never be exposed** — not in URLs/routes, API/Inertia responses, DTOs, or route model binding. Public-facing entities are looked up and rendered by `ulid`; internal-only entities are never exposed to clients at all. Enforce this in DTOs (omit `id` from output `Data` classes for public-facing entities) and in routing (bind by `ulid`, not `id`).
- Public-facing models `use App\Concerns\HasUlid`. The trait composes Laravel's `HasUlids`, generates `ulid` on create when omitted, merges `ulid` into fillable if missing, and binds routes on `ulid`.

## Enum-Type Data

- Fixed-set/categorical data (e.g. project type, user roles, billing type) is modeled as a PHP backed enum at the application level (`App\Enums\*`), never as free-form strings scattered through the codebase.
- At the database level, the backing column is a plain `string` column — **never a DB-native `enum` type**. The model casts the column to the PHP enum via `casts()`; validity is enforced at the application layer (requests/services), not the schema.

## Migrations

- When defining a foreign key column, use `$table->foreignIdFor(Model::class)` instead of `$table->foreignId('model_id')`. `foreignIdFor` derives the column name from the model and keeps the column type in sync with the referenced model's key type, instead of hardcoding a string column name.

## Frontend Folder Structure

- `resources/js/components/` is for **globally reusable** building blocks — generic primitives with no knowledge of a specific feature (e.g. a `Dialog` wrapper, a `Table`, a `Pagination` control, a generic `ConfirmDeleteDialog`). Anything here should be usable by multiple, unrelated pages without modification.
- Feature-specific components that only make sense in the context of one page — most commonly a create/edit form for a single entity — live **alongside that page** under `resources/js/pages/<feature>/`, not under `resources/js/components/`. Example: a project create/edit form belongs at `resources/js/pages/projects/project-form-dialog.tsx`, not `resources/js/components/projects/project-form-dialog.tsx`.
- Rule of thumb: if removing the page would make the component pointless, it belongs in `pages/`; if it would still be useful elsewhere, it belongs in `components/`.

## Wayfinder Routes

- When wiring frontend code to backend endpoints, import from Wayfinder's generated `resources/js/routes/*` (named route helpers, e.g. `import { store, update, destroy } from '@/routes/projects'`), not from `resources/js/actions/App/Http/Controllers/*` (controller action helpers). Both are generated and both expose `.form()`, but routes are the standard for this app — controller action imports couple frontend code to backend controller/method names instead of the route itself.
- This applies to new code. Existing usages of controller action imports are not being retrofitted as part of this rule.

## Code Style

- Readability matters more than compactness. Anywhere in the codebase, avoid inlining a method call as an argument to another call when it obscures what's being passed — assign the intermediate result to a well-named variable first, then pass the variable. This isn't scoped to any particular class or layer; apply it wherever one call's result feeds directly into another.
  - Example: prefer `$slug = $this->uniqueSlug($data->name); $this->clients->create($data, $slug);` over `$this->clients->create($data, $this->uniqueSlug($data->name))`.
  - Example: prefer `$data = StoreClientData::from($request->validated()); $this->clients->create($data);` over `$this->clients->create(StoreClientData::from($request->validated()))`.
  - Rule of thumb: a single, obvious pass-through like `response()->json($this->clients->find($slug))` is fine — there's only one transformation and its intent is self-evident. Once a value is being *constructed or transformed* (DTO hydration, slug generation, etc.) before being handed to another call, that construction step gets its own named variable.
  - Don't take this shortcut even when it saves a line — favor the version a reader can scan without holding extra state in their head.

## Tests & Best Practices

- Any route protected by authentication middleware (`auth`) must have a feature test asserting that an unauthenticated request is rejected with the appropriate response, rather than reaching the controller — `401 Unauthorized` for JSON/API-style requests (e.g. `postJson`/`getJson`), or a redirect to the login route for standard web/Inertia requests. Most CRUD endpoints in this app sit behind `auth`, so this test is expected alongside the happy-path test for every new resource route.
- Model relations (`hasMany`, `belongsTo`, etc.) must have a unit test asserting the relation method returns the correct relation type (e.g. `expect($client->projects())->toBeInstanceOf(HasMany::class)`), not just that the resulting collection/model has the expected content. Asserting on content alone (e.g. an empty collection) can pass even if the relation is misconfigured.
- Test every aspect of the main flow, not just the happy path once. If a `FormRequest` declares validation rules, there must be a feature test asserting invalid input is rejected (`assertInvalid`) — an untested rule can silently stop being enforced. If a service generates a unique value (e.g. `ClientService::uniqueSlug()`), there must be a test that triggers a collision (e.g. creating two records with the same input) and asserts the generated values actually differ.
- Any Store endpoint that persists a record should assert on the row count (e.g. `assertDatabaseCount`) where relevant, not just that a response came back — this catches cases like a validation failure or a slug collision silently producing duplicate/missing rows.
