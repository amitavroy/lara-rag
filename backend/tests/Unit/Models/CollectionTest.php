<?php

use App\Models\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Tests\TestCase;

uses(TestCase::class);

test('user is a belongs to relation', function () {
    expect((new Collection)->user())->toBeInstanceOf(BelongsTo::class);
});
