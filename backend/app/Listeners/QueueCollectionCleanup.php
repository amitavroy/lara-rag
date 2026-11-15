<?php

namespace App\Listeners;

use App\Events\CollectionDeleted;
use App\Jobs\PurgeCollection;

class QueueCollectionCleanup
{
    public function handle(CollectionDeleted $event): void
    {
        PurgeCollection::dispatch($event->collectionId);
    }
}
