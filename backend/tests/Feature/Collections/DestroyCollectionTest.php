<?php

use App\Events\CollectionDeleted;
use App\Models\Collection;
use App\Models\User;
use Illuminate\Support\Facades\Event;

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

test('a non-owner gets 403 and the row is untouched', function () {
    Event::fake([CollectionDeleted::class]);
    $collection = Collection::factory()->create();

    $this->actingAs(User::factory()->create())
        ->delete(route('collections.destroy', $collection))
        ->assertForbidden();

    $this->assertNotSoftDeleted($collection);
    Event::assertNotDispatched(CollectionDeleted::class);
});
