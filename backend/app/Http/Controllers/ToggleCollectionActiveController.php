<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Services\CollectionService;
use Illuminate\Http\RedirectResponse;

class ToggleCollectionActiveController extends Controller
{
    public function __construct(private CollectionService $collections) {}

    public function __invoke(Collection $collection): RedirectResponse
    {
        $this->collections->toggleActive($collection);

        return back();
    }
}
