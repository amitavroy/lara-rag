<?php

use App\Http\Controllers\CollectionController;
use App\Http\Controllers\ToggleCollectionActiveController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');

    Route::resource('collections', CollectionController::class)
        ->except('show')
        ->middlewareFor(['edit', 'update'], 'can:update,collection')
        ->middlewareFor('destroy', 'can:delete,collection');

    Route::patch('collections/{collection}/toggle-active', ToggleCollectionActiveController::class)
        ->middleware('can:toggle,collection')
        ->name('collections.toggle-active');
});

require __DIR__.'/settings.php';
