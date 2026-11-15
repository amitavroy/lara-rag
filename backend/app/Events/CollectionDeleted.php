<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

class CollectionDeleted
{
    use Dispatchable;

    public function __construct(public int $collectionId) {}
}
