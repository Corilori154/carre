<?php

namespace App\Http\Controllers;

use App\Models\Artwork;
use Inertia\Inertia;

class PublicComposedGalleryController extends Controller
{
    public function index()
    {
        $artworks = Artwork::orderBy('title')->get(['id', 'title']);

        $selectedArtworkId = request()->integer('artwork_id') ?: $artworks->first()?->id;

        $selectedArtwork = null;

        if ($selectedArtworkId) {
            $artwork = Artwork::with('images')->find($selectedArtworkId);

            if ($artwork) {
                $slots = array_fill(0, 9, null);

                foreach ($artwork->images as $index => $image) {
                    $position = $image->composition_position
                        ? $image->composition_position - 1
                        : $index;

                    if ($position >= 0 && $position < 9) {
                        $slots[$position] = [
                            'id' => $image->id,
                            'url' => asset('storage/' . $image->image_path),
                            'rotation' => $image->rotation ?? 0,
                            'position' => $position + 1,
                        ];
                    }
                }

                $selectedArtwork = [
                    'id' => $artwork->id,
                    'title' => $artwork->title,
                    'slots' => $slots,
                ];
            }
        }

        return Inertia::render('Public/ComposedGallery', [
            'artworks' => $artworks,
            'selectedArtworkId' => $selectedArtworkId,
            'selectedArtwork' => $selectedArtwork,
        ]);
    }
}