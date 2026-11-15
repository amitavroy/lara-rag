<?php

use App\Models\Collection;
use App\Models\User;

test('guests are redirected', function () {
    $collection = Collection::factory()->create();

    $this->patch(route('collections.toggle-active', $collection))->assertRedirect(route('login'));
});

test('toggling flips active to inactive', function () {
    $collection = Collection::factory()->create();

    $this->actingAs($collection->user)
        ->patch(route('collections.toggle-active', $collection))
        ->assertRedirect();

    expect($collection->refresh()->is_active)->toBeFalse();
});

test('toggling flips inactive to active', function () {
    $collection = Collection::factory()->inactive()->create();

    $this->actingAs($collection->user)
        ->patch(route('collections.toggle-active', $collection))
        ->assertRedirect();

    expect($collection->refresh()->is_active)->toBeTrue();
});

test('a non-owner gets 403', function () {
    $collection = Collection::factory()->create();

    $this->actingAs(User::factory()->create())
        ->patch(route('collections.toggle-active', $collection))
        ->assertForbidden();

    expect($collection->refresh()->is_active)->toBeTrue();
});
