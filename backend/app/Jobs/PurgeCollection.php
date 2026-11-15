<?php

namespace App\Jobs;

use App\Services\CollectionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class PurgeCollection implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $collectionId) {}

    public function handle(CollectionService $service): void
    {
        $service->purge($this->collectionId);
    }
}
