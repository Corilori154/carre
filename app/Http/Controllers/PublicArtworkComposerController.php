<?php

namespace App\Http\Controllers;

use App\Models\Artwork;
use App\Models\Gallery;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class PublicArtworkComposerController extends Controller
{
    public function index(?Gallery $gallery = null)
    {
        if ($gallery) {
            $artworksQuery = Artwork::whereHas(
                'galleries',
                fn ($query) => $query->whereKey($gallery->id),
            );
        } else {
            $artworksQuery = Artwork::where('is_public', true);
        }

        $artworks = $artworksQuery
            ->with(['images' => function ($query) {
                $query->orderBy('position');
            }])
            ->latest()
            ->get()
            ->map(function ($artwork) {
                return [
                    'id' => $artwork->id,
                    'title' => $artwork->title,
                    'background_color' => $artwork->background_color,
                    'is_public' => $artwork->is_public,
                    'images' => $artwork->images->map(function ($image) {
                        return [
                            'id' => $image->id,
                            'position' => $image->position,
                            'url' => Storage::url($image->image_path),
                        ];
                    })->values(),
                ];
            });

        return Inertia::render('Public/ComposeArtwork', [
            'artworks' => $artworks,
            'gallery' => $gallery?->only('name', 'slug'),
        ]);
    }
}
