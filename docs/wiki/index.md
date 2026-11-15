# Collections

A collection is a named group a user owns. Documents will be uploaded into collections and a collection can be queried as a unit. Users can switch it on or off without deleting it.

## Rules

- Owner-only: edit, activate/deactivate and delete are allowed only for the user who created the collection (`CollectionPolicy`, applied as route middleware, non-owners get 403).
- Name: required, max 255, unique per user among non-deleted collections. Description: optional, max 2000.
- New collections are active.
- Public ID is the `ulid`; the internal `id` is never sent to the client.
- No detail page yet (`show` is not routed). It arrives with documents.

## Name uniqueness

```
create/rename -> FormRequest unique(user_id, name, ignoring soft-deleted [and self on update])
              -> Postgres partial unique index (user_id, name) where deleted_at is null
```

The DB index is the backstop. It ignores soft-deleted rows, so a name can be reused while a deleted collection waits for its purge job.

## Delete flow

```
DELETE /collections/{ulid} -> soft delete -> CollectionDeleted(id)
  -> QueueCollectionCleanup -> PurgeCollection job
  -> job reloads with trashed:
       missing           -> stop
       restored          -> stop
       still soft-deleted -> force delete
```

| State | After delete | After purge job |
|-------|--------------|-----------------|
| Row | soft-deleted, hidden from lists, 404 on routes | permanently removed |

Purging a collection's documents, chunks and embeddings is not built yet. It plugs into `CollectionService::purge` (`TODO(documents)`).

## Active toggle

`PATCH /collections/{ulid}/toggle-active` flips `is_active`. It is a separate single-action controller, not part of the resource controller.
