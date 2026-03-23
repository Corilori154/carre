<?php

namespace App\Http\Controllers;

use App\Models\Artwork;
use App\Models\SettingTime;
use Inertia\Inertia;

class PublicGalleryController extends Controller
{
    public function index()
    {
        $artworks = Artwork::with(['images' => function ($query) {
            $query->orderBy('position');
        }])
        ->latest()
        ->get()
        ->map(function ($artwork) {
            return [
                'id' => $artwork->id,
                'title' => $artwork->title,
                'images' => $artwork->images->map(function ($image) {
                    return [
                        'id' => $image->id,
                        'position' => $image->position,
                        'url' => Storage::url($image->image_path),
                    ];
                })->values(),
            ];
        });

        return Inertia::render('Public/Gallery', [
            'artworks' => $artworks,
            'shuffleIntervalSeconds' => (int) SettingTime::getValue('shuffle_interval_seconds', 10),
        ]);
    }
}