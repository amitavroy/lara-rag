<?php

use App\Models\Collection;
use App\Models\User;

test('guests are redirected from create and store', function () {
    $this->get(route('collections.create'))->assertRedirect(route('login'));
    $this->post(route('collections.store'), ['name' => 'Docs'])->assertRedirect(route('login'));
});

test('the create page renders', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('collections.create'))
        ->assertOk();
});

test('a user can create an active collection', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('collections.store'), ['name' => 'Handbooks', 'description' => 'HR docs'])
        ->assertRedirect(route('collections.index'));

    $this->assertDatabaseCount('collections', 1);
    $this->assertDatabaseHas('collections', [
        'user_id' => $user->id,
        'name' => 'Handbooks',
        'is_active' => true,
    ]);
});

test('the description is optional', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('collections.store'), ['name' => 'Handbooks'])
        ->assertValid();

    $this->assertDatabaseCount('collections', 1);
});

test('invalid input is rejected', function (array $payload, string $field) {
    $this->actingAs(User::factory()->create())
        ->post(route('collections.store'), $payload)
        ->assertInvalid($field);

    $this->assertDatabaseCount('collections', 0);
})->with([
    'missing name' => [[], 'name'],
    'name too long' => [['name' => str_repeat('a', 256)], 'name'],
    'description too long' => [['name' => 'Docs', 'description' => str_repeat('a', 2001)], 'description'],
]);

test('a duplicate name for the same user is rejected', function () {
    $user = User::factory()->create();
    Collection::factory()->for($user)->create(['name' => 'Docs']);

    $this->actingAs($user)
        ->post(route('collections.store'), ['name' => 'Docs'])
        ->assertInvalid('name');

    $this->assertDatabaseCount('collections', 1);
});

test('the same name is allowed for a different user', function () {
    Collection::factory()->create(['name' => 'Docs']);

    $this->actingAs(User::factory()->create())
        ->post(route('collections.store'), ['name' => 'Docs'])
        ->assertValid();

    $this->assertDatabaseCount('collections', 2);
});

test('the same name is allowed after the original is soft deleted', function () {
    $user = User::factory()->create();
    Collection::factory()->for($user)->create(['name' => 'Docs'])->delete();

    $this->actingAs($user)
        ->post(route('collections.store'), ['name' => 'Docs'])
        ->assertValid();

    $this->assertDatabaseCount('collections', 2);
});
