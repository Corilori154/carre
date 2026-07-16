<?php

namespace App\Http\Controllers;

use App\Models\Artwork;
use App\Models\Gallery;
use App\Models\SettingTime;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

class PublicGalleryController extends Controller
{
    public function index(?Gallery $gallery = null)
    {
        $artworksQuery = Artwork::query();

        if ($gallery) {
            $artworksQuery->whereHas('galleries', fn ($query) => $query->whereKey($gallery->id));
        }

        $artworks = $artworksQuery->with(['images' => function ($query) {
            $query->orderBy('position');
        }])
        ->latest()
        ->get()
        ->map(function ($artwork) {
           return [
            'id' => $artwork->id,
            'title' => $artwork->title,
            'background_color' => $artwork->background_color,
            'generated_count' => $artwork->generated_count,
            'created_at' => $artwork->created_at?->format('d.m.Y'),
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
            'gallery' => $gallery?->only('name', 'slug'),
            'shuffleIntervalSeconds' => (int) SettingTime::getValue('shuffle_interval_seconds', 10),
        ]);
    }
}
