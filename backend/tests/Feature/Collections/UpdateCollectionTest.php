<?php

use App\Models\Collection;
use App\Models\User;

test('guests are redirected from edit and update', function () {
    $collection = Collection::factory()->create();

    $this->get(route('collections.edit', $collection))->assertRedirect(route('login'));
    $this->put(route('collections.update', $collection), ['name' => 'X'])->assertRedirect(route('login'));
});

test('the owner can see the edit page', function () {
    $collection = Collection::factory()->create();

    $this->actingAs($collection->user)
        ->get(route('collections.edit', $collection))
        ->assertOk();
});

test('the owner can update a collection', function () {
    $collection = Collection::factory()->create();

    $this->actingAs($collection->user)
        ->put(route('collections.update', $collection), ['name' => 'Renamed', 'description' => 'New'])
        ->assertRedirect(route('collections.index'));

    expect($collection->refresh())
        ->name->toBe('Renamed')
        ->description->toBe('New');
});

test('saving with an unchanged name passes', function () {
    $collection = Collection::factory()->create(['name' => 'Same']);

    $this->actingAs($collection->user)
        ->put(route('collections.update', $collection), ['name' => 'Same'])
        ->assertValid();
});

test('renaming to another of your own collection names fails', function () {
    $user = User::factory()->create();
    Collection::factory()->for($user)->create(['name' => 'Taken']);
    $collection = Collection::factory()->for($user)->create();

    $this->actingAs($user)
        ->put(route('collections.update', $collection), ['name' => 'Taken'])
        ->assertInvalid('name');
});

test('a missing name is rejected', function () {
    $collection = Collection::factory()->create();

    $this->actingAs($collection->user)
        ->put(route('collections.update', $collection), [])
        ->assertInvalid('name');
});

test('a non-owner gets 403 on edit and update', function () {
    $collection = Collection::factory()->create(['name' => 'Original']);
    $other = User::factory()->create();

    $this->actingAs($other)->get(route('collections.edit', $collection))->assertForbidden();
    $this->actingAs($other)
        ->put(route('collections.update', $collection), ['name' => 'Hacked'])
        ->assertForbidden();

    expect($collection->refresh()->name)->toBe('Original');
});
