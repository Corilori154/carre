<?php

namespace App\Http\Controllers;

use App\Models\Artwork;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class PublicArtworkComposerController extends Controller
{
    public function index()
    {
        $artworks = Artwork::where('is_public', true)
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
        ]);
    }
}