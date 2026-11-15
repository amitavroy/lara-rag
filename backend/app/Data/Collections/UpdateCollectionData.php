<?php

namespace App\Data\Collections;

use Spatie\LaravelData\Data;

class UpdateCollectionData extends Data
{
    public function __construct(
        public string $name,
        public ?string $description,
    ) {}
}
