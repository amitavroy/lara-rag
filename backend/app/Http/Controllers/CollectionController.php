<?php

namespace App\Http\Controllers;

use App\Data\Collections\CollectionData;
use App\Data\Collections\StoreCollectionData;
use App\Data\Collections\UpdateCollectionData;
use App\Http\Requests\Collections\StoreCollectionRequest;
use App\Http\Requests\Collections\UpdateCollectionRequest;
use App\Models\Collection;
use App\Services\CollectionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CollectionController extends Controller
{
    public function __construct(private CollectionService $collections) {}

    public function index(Request $request): Response
    {
        $collections = $this->collections->list($request->user()->id);

        return Inertia::render('collections/index', [
            'collections' => $collections,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('collections/create');
    }

    public function store(StoreCollectionRequest $request): RedirectResponse
    {
        $data = StoreCollectionData::from($request->validated());

        $this->collections->create($request->user()->id, $data);

        return to_route('collections.index');
    }

    public function edit(Collection $collection): Response
    {
        $collectionData = CollectionData::fromModel($collection);

        return Inertia::render('collections/edit', [
            'collection' => $collectionData,
        ]);
    }

    public function update(UpdateCollectionRequest $request, Collection $collection): RedirectResponse
    {
        $data = UpdateCollectionData::from($request->validated());

        $this->collections->update($collection, $data);

        return to_route('collections.index');
    }

    public function destroy(Collection $collection): RedirectResponse
    {
        $this->collections->delete($collection);

        return to_route('collections.index');
    }
}
