<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\ArtworkController;
use App\Http\Controllers\Admin\GalleryController;
use App\Http\Controllers\PublicGalleryController;
use App\Http\Controllers\Admin\SettingTimeController;
use App\Http\Controllers\PublicComposedGalleryController;
use App\Http\Controllers\Admin\ArtworkComposerController;
use App\Http\Controllers\PublicArtworkComposerController;
use App\Http\Controllers\GalleryAccessController;
use App\Http\Controllers\GeneratedCompositionController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/test', function () {
    return Inertia::render('TestPage');
});

Route::get('/test2', function () {
    return Inertia::render('TestPage');
});

Route::get('/composed-gallery', [PublicComposedGalleryController::class, 'index'])->name('composed-gallery');

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/gallery', [PublicGalleryController::class, 'index'])->name('gallery');


Route::get('/compose-your-artwork', [PublicArtworkComposerController::class, 'index'])
    ->name('public.compose-artwork');
Route::post('/generated-compositions', [GeneratedCompositionController::class, 'store'])
    ->middleware('throttle:30,1')
    ->name('generated-compositions.store');

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    Route::get('/artworks', [ArtworkController::class, 'index'])->name('artworks.index');
    Route::get('/artworks/create', [ArtworkController::class, 'create'])->name('artworks.create');
    Route::post('/artworks', [ArtworkController::class, 'store'])->name('artworks.store');
    Route::get('/artworks/{artwork}/edit', [ArtworkController::class, 'edit'])->name('artworks.edit');
    Route::put('/artworks/{artwork}', [ArtworkController::class, 'update'])->name('artworks.update');
    Route::delete('/artworks/{artwork}', [ArtworkController::class, 'destroy'])->name('artworks.destroy');

    Route::get('/setting-times', [SettingTimeController::class, 'edit'])->name('setting-times.edit');
    Route::put('/setting-times', [SettingTimeController::class, 'update'])->name('setting-times.update');

    Route::get('/composer', [ArtworkComposerController::class, 'edit'])->name('composer.edit');
    Route::put('/composer', [ArtworkComposerController::class, 'update'])->name('composer.update');

    Route::get('/galleries', [GalleryController::class, 'index'])->name('galleries.index');
    Route::post('/galleries', [GalleryController::class, 'store'])->name('galleries.store');
    Route::get('/galleries/{gallery}/edit', [GalleryController::class, 'edit'])->name('galleries.edit');
    Route::patch('/galleries/{gallery}', [GalleryController::class, 'update'])->name('galleries.update');
    Route::put('/galleries/{gallery}/artworks', [GalleryController::class, 'updateArtworks'])->name('galleries.artworks.update');
    Route::delete('/galleries/{gallery}', [GalleryController::class, 'destroy'])->name('galleries.destroy');
    Route::put('/galleries/{gallery}/access', [GalleryController::class, 'resetAccess'])->name('galleries.access.reset');
});

Route::get('/{gallery}/access', [GalleryAccessController::class, 'create'])->name('galleries.access');
Route::post('/{gallery}/access', [GalleryAccessController::class, 'store'])
    ->middleware('throttle:6,1')
    ->name('galleries.access.store');
Route::post('/{gallery}/generated-compositions', [GeneratedCompositionController::class, 'store'])
    ->middleware(['gallery.device', 'throttle:30,1'])
    ->name('galleries.generated-compositions.store');
Route::get('/{gallery}/gallery', [PublicGalleryController::class, 'index'])
    ->middleware('gallery.device')
    ->name('galleries.gallery');
Route::get('/{gallery}/compose-your-artwork', [PublicArtworkComposerController::class, 'index'])
    ->middleware('gallery.device')
    ->name('galleries.compose-artwork');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
