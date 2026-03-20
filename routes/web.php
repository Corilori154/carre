<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\ArtworkController;
use App\Http\Controllers\PublicGalleryController;
use App\Http\Controllers\Admin\SettingTimeController;
use App\Http\Controllers\PublicComposedGalleryController;
use App\Http\Controllers\Admin\ArtworkComposerController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/test', function () {
    return Inertia::render('TestPage');
});

Route::get('/composed-gallery', [PublicComposedGalleryController::class, 'index'])->name('composed-gallery');

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/gallery', [PublicGalleryController::class, 'index'])->name('gallery');

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    Route::get('/artworks', [ArtworkController::class, 'index'])->name('artworks.index');
    Route::get('/artworks/create', [ArtworkController::class, 'create'])->name('artworks.create');
    Route::post('/artworks', [ArtworkController::class, 'store'])->name('artworks.store');
    Route::delete('/artworks/{artwork}', [ArtworkController::class, 'destroy'])->name('artworks.destroy');

    Route::get('/setting-times', [SettingTimeController::class, 'edit'])->name('setting-times.edit');
    Route::put('/setting-times', [SettingTimeController::class, 'update'])->name('setting-times.update');

    Route::get('/composer', [ArtworkComposerController::class, 'edit'])->name('composer.edit');
    Route::put('/composer', [ArtworkComposerController::class, 'update'])->name('composer.update');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';