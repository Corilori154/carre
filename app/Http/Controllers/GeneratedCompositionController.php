<?php

namespace App\Http\Controllers;

use App\Models\Artwork;
use App\Models\GeneratedComposition;
use App\Models\Gallery;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GeneratedCompositionController extends Controller
{
    public function store(Request $request, ?Gallery $gallery = null): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'artwork_id' => ['required', 'integer', Rule::exists('artworks', 'id')],
            'slots' => ['required', 'array', 'size:9'],
            'slots.*' => ['nullable', 'array'],
            'slots.*.image_id' => ['required_with:slots.*', 'integer'],
            'slots.*.rotation' => ['required_with:slots.*', 'integer', Rule::in([0, 90, 180, 270])],
        ]);

        $artwork = Artwork::findOrFail($validated['artwork_id']);

        if ($gallery) {
            abort_unless($gallery->artworks()->whereKey($artwork->id)->exists(), 403);
        } else {
            abort_unless($artwork->is_public, 403);
        }

        $allowedImageIds = $artwork->images()->pluck('id');

        $slots = collect($validated['slots'])->map(function ($slot) use ($allowedImageIds) {
            if ($slot === null) {
                return null;
            }

            abort_unless($allowedImageIds->contains($slot['image_id']), 422, 'Une image ne correspond pas au tableau sélectionné.');

            return [
                'image_id' => (int) $slot['image_id'],
                'rotation' => (int) $slot['rotation'],
            ];
        })->values()->all();

        $composition = [
            'background_color' => strtolower($artwork->background_color ?: '#f5f5f4'),
            'slots' => $slots,
        ];
        $fingerprint = hash('sha256', json_encode($composition, JSON_THROW_ON_ERROR));

        try {
            GeneratedComposition::create([
                'artwork_id' => $artwork->id,
                'gallery_id' => $gallery?->id,
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'fingerprint' => $fingerprint,
                'composition' => $composition,
            ]);
        } catch (UniqueConstraintViolationException) {
            return response()->json([
                'message' => 'Ce tableau a déjà été généré et téléchargé. Modifiez au moins une image, sa position ou sa rotation pour créer un tableau unique.',
            ], 409);
        }

        return response()->json(['message' => 'Composition réservée.'], 201);
    }
}
