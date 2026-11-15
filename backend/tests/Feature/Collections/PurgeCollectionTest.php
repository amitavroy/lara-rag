<?php

use App\Events\CollectionDeleted;
use App\Jobs\PurgeCollection;
use App\Listeners\QueueCollectionCleanup;
use App\Models\Collection;
use Illuminate\Support\Facades\Queue;

test('the job force deletes a trashed collection', function () {
    $collection = Collection::factory()->create();
    $collection->delete();

    PurgeCollection::dispatchSync($collection->id);

    $this->assertDatabaseMissing('collections', ['id' => $collection->id]);
});

test('the job leaves a restored collection alone', function () {
    $collection = Collection::factory()->create();
    $collection->delete();
    $collection->restore();

    PurgeCollection::dispatchSync($collection->id);

    $this->assertDatabaseHas('collections', ['id' => $collection->id]);
});

test('the job does not fail for an unknown id', function () {
    PurgeCollection::dispatchSync(999999);

    $this->assertDatabaseCount('collections', 0);
});

test('the listener queues the purge job', function () {
    Queue::fake();

    (new QueueCollectionCleanup)->handle(new CollectionDeleted(42));

    Queue::assertPushed(PurgeCollection::class, fn (PurgeCollection $job) => $job->collectionId === 42);
});
