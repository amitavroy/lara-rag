<?php

namespace App\Data\Collections;

use App\Models\Collection;
use Carbon\CarbonImmutable;
use Spatie\LaravelData\Data;

class CollectionData extends Data
{
    public function __construct(
        public string $ulid,
        public string $name,
        public ?string $description,
        public bool $is_active,
        public CarbonImmutable $created_at,
    ) {}

    public static function fromModel(Collection $collection): self
    {
        return new self(
            ulid: $collection->ulid,
            name: $collection->name,
            description: $collection->description,
            is_active: $collection->is_active,
            created_at: $collection->created_at->toImmutable(),
        );
    }
}
