<?php

namespace App\Services;

use App\Data\Collections\CollectionData;
use App\Data\Collections\StoreCollectionData;
use App\Data\Collections\UpdateCollectionData;
use App\Events\CollectionDeleted;
use App\Models\Collection;
use App\Repositories\CollectionRepository;
use Spatie\LaravelData\DataCollection;

class CollectionService
{
    public function __construct(private CollectionRepository $collections) {}

    /**
     * @return DataCollection<int, CollectionData>
     */
    public function list(int $userId): DataCollection
    {
        return $this->collections->listForUser($userId);
    }

    public function create(int $userId, StoreCollectionData $data): CollectionData
    {
        return $this->collections->create($userId, $data);
    }

    public function update(Collection $collection, UpdateCollectionData $data): CollectionData
    {
        return $this->collections->update($collection, $data);
    }

    public function toggleActive(Collection $collection): CollectionData
    {
        $active = ! $collection->is_active;

        return $this->collections->setActive($collection, $active);
    }

    public function delete(Collection $collection): void
    {
        $collectionId = $collection->id;

        $this->collections->delete($collection);

        CollectionDeleted::dispatch($collectionId);
    }

    public function purge(int $collectionId): void
    {
        $collection = $this->collections->findTrashed($collectionId);

        if ($collection === null || ! $collection->trashed()) {
            return;
        }

        // TODO(documents): purge the collection's documents, chunks and embeddings first.
        $this->collections->forceDelete($collection);
    }
}
