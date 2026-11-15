<?php

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Tests\TestCase;

uses(TestCase::class);

test('collections is a has many relation', function () {
    expect((new User)->collections())->toBeInstanceOf(HasMany::class);
});
