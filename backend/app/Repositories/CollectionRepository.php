<?php

namespace App\Repositories;

use App\Data\Collections\CollectionData;
use App\Data\Collections\StoreCollectionData;
use App\Data\Collections\UpdateCollectionData;
use App\Models\Collection;
use Spatie\LaravelData\DataCollection;

class CollectionRepository
{
    /**
     * @return DataCollection<int, CollectionData>
     */
    public function listForUser(int $userId): DataCollection
    {
        $collections = Collection::query()
            ->where('user_id', $userId)
            ->latest()
            ->get()
            ->map(fn (Collection $collection): CollectionData => CollectionData::fromModel($collection));

        return CollectionData::collect($collections, DataCollection::class);
    }

    public function create(int $userId, StoreCollectionData $data): CollectionData
    {
        $collection = new Collection($data->toArray());
        $collection->user_id = $userId;
        $collection->save();

        return CollectionData::fromModel($collection->refresh());
    }

    public function update(Collection $collection, UpdateCollectionData $data): CollectionData
    {
        $collection->update($data->toArray());

        return CollectionData::fromModel($collection);
    }

    public function setActive(Collection $collection, bool $active): CollectionData
    {
        $collection->update(['is_active' => $active]);

        return CollectionData::fromModel($collection);
    }

    public function delete(Collection $collection): void
    {
        $collection->delete();
    }

    public function findTrashed(int $id): ?Collection
    {
        return Collection::withTrashed()->find($id);
    }

    public function forceDelete(Collection $collection): void
    {
        $collection->forceDelete();
    }
}
