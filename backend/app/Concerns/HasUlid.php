<?php

namespace App\Concerns;

use Illuminate\Database\Eloquent\Concerns\HasUlids;

trait HasUlid
{
    use HasUlids;

    public function initializeHasUlid(): void
    {
        if (! in_array('ulid', $this->fillable, true)) {
            $this->mergeFillable(['ulid']);
        }
    }

    /**
     * @return array<int, string>
     */
    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }
}
