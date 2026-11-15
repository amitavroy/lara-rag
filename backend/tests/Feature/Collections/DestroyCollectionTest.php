<?php

use App\Events\CollectionDeleted;
use App\Jobs\PurgeCollection;
use App\Models\Collection;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;

test('guests are redirected', function () {
    $collection = Collection::factory()->create();

    $this->delete(route('collections.destroy', $collection))->assertRedirect(route('login'));
});

test('the owner soft deletes a collection and the event is dispatched', function () {
    Event::fake([CollectionDeleted::class]);
    $collection = Collection::factory()->create();

    $this->actingAs($collection->user)
        ->delete(route('collections.destroy', $collection))
        ->assertRedirect(route('collections.index'));

    $this->assertSoftDeleted($collection);
    Event::assertDispatched(
        CollectionDeleted::class,
        fn (CollectionDeleted $event) => $event->collectionId === $collection->id,
    );
});

test('deleting through the route queues the purge job', function () {
    Queue::fake();
    $collection = Collection::factory()->create();

    $this->actingAs($collection->user)->delete(route('collections.destroy', $collection));

    Queue::assertPushed(PurgeCollection::class, fn (PurgeCollection $job) => $job->collectionId === $collection->id);
});

test('a non-owner gets 403 and the row is untouched', function () {
    Event::fake([CollectionDeleted::class]);
    $collection = Collection::factory()->create();

    $this->actingAs(User::factory()->create())
        ->delete(route('collections.destroy', $collection))
        ->assertForbidden();

    $this->assertNotSoftDeleted($collection);
    Event::assertNotDispatched(CollectionDeleted::class);
});
