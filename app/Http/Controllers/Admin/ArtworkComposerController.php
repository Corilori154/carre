<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Artwork;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

class ArtworkComposerController extends Controller
{
    public function edit(Request $request)
    {
        $artworks = Artwork::orderBy('title')->get(['id', 'title']);

        $selectedArtworkId = $request->integer('artwork_id') ?: $artworks->first()?->id;

        $selectedArtwork = null;

        if ($selectedArtworkId) {
            $selectedArtwork = Artwork::with('images')
                ->find($selectedArtworkId);

            if ($selectedArtwork) {
                $selectedArtwork = [
                    'id' => $selectedArtwork->id,
                    'title' => $selectedArtwork->title,
                    'images' => $selectedArtwork->images
                        ->sortBy('id')
                        ->map(function ($image, $index) {
                            return [
                                'id' => $image->id,
                                'url' => Storage::url($image->image_path),
                                'rotation' => $image->rotation ?? 0,
                                'composition_position' => $image->composition_position,
                                'fallback_position' => $index,
                            ];
                        })
                        ->values(),
                ];
            }
        }

        return Inertia::render('Admin/Composer/Edit', [
            'artworks' => $artworks,
            'selectedArtworkId' => $selectedArtworkId,
            'selectedArtwork' => $selectedArtwork,
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'artwork_id' => ['required', 'exists:artworks,id'],
            'slots' => ['required', 'array', 'size:9'],
            'slots.*.image_id' => ['nullable', 'exists:artwork_images,id'],
            'slots.*.rotation' => ['required', 'integer', 'in:0,90,180,270'],
        ]);

        $artwork = Artwork::with('images')->findOrFail($validated['artwork_id']);
        $allowedImageIds = $artwork->images->pluck('id')->all();

        $usedIds = collect($validated['slots'])
            ->pluck('image_id')
            ->filter()
            ->values();

        if ($usedIds->unique()->count() !== $usedIds->count()) {
            return back()->withErrors([
                'slots' => 'Une image ne peut apparaître qu’une seule fois dans la grille.',
            ]);
        }

        foreach ($usedIds as $imageId) {
            if (! in_array($imageId, $allowedImageIds, true)) {
                return back()->withErrors([
                    'slots' => 'Une image ne correspond pas au tableau sélectionné.',
                ]);
            }
        }

        foreach ($artwork->images as $image) {
            $image->update([
                'composition_position' => null,
                'rotation' => 0,
            ]);
        }

        foreach ($validated['slots'] as $index => $slot) {
            if (! $slot['image_id']) {
                continue;
            }

            $image = $artwork->images->firstWhere('id', $slot['image_id']);

            if (! $image) {
                continue;
            }

            $image->update([
                'composition_position' => $index + 1,
                'rotation' => $slot['rotation'],
            ]);
        }

        return redirect()->route('admin.composer.edit', [
            'artwork_id' => $validated['artwork_id'],
        ]);
    }
}