<?php

use App\Models\Collection;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $this->get(route('collections.index'))->assertRedirect(route('login'));
});

test('a user sees only their own non-deleted collections', function () {
    $user = User::factory()->create();
    $mine = Collection::factory()->for($user)->create();
    Collection::factory()->for($user)->create()->delete();
    Collection::factory()->create();

    $this->actingAs($user)
        ->get(route('collections.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('collections/index')
            ->has('collections', 1)
            ->where('collections.0.ulid', $mine->ulid));
});

test('collection props expose the ulid and never the id', function () {
    $user = User::factory()->create();
    Collection::factory()->for($user)->create();

    $this->actingAs($user)
        ->get(route('collections.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('collections.0.ulid')
            ->missing('collections.0.id')
            ->missing('collections.0.user_id'));
});
