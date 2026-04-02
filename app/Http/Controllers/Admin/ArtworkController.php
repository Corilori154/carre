<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Artwork;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ArtworkController extends Controller
{
    public function index()
    {
        $artworks = Artwork::with(['images' => function ($query) {
            $query->orderBy('position');
        }])->latest()->get()->map(function ($artwork) {
            return [
                'id' => $artwork->id,
                'title' => $artwork->title,
                'background_color' => $artwork->background_color,
                'is_published' => $artwork->is_published,
                'images' => $artwork->images->map(function ($image) {
                    return [
                        'id' => $image->id,
                        'position' => $image->position,
                        'url' => Storage::url($image->image_path),
                    ];
                })->values(),
            ];
        });

        return Inertia::render('Admin/Artworks/Index', [
            'artworks' => $artworks,
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Artworks/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'images' => ['required', 'array', 'size:9'],
            'images.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'background_color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        $artwork = Artwork::create([
            'title' => $validated['title'],
            'is_published' => false,
            'background_color' => $validated['background_color'],
        ]);

        foreach ($validated['images'] as $index => $file) {
            $path = $file->store('artworks', 'public');

            $artwork->images()->create([
                'image_path' => $path,
                'position' => $index + 1,
            ]);
        }

        return redirect()->route('admin.artworks.index');
    }

    public function edit(Artwork $artwork)
    {
        $artwork->load(['images' => function ($query) {
            $query->orderBy('position');
        }]);

        return Inertia::render('Admin/Artworks/Edit', [
            'artwork' => [
                'id' => $artwork->id,
                'title' => $artwork->title,
                'background_color' => $artwork->background_color,
                'images' => $artwork->images->map(function ($image) {
                    return [
                        'id' => $image->id,
                        'position' => $image->position,
                        'url' => Storage::url($image->image_path),
                    ];
                })->values(),
            ],
        ]);
    }

    public function update(Request $request, Artwork $artwork)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'images' => ['required', 'array', 'size:9'],
            'images.*' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'background_color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        $artwork->update([
            'title' => $validated['title'],
            'background_color' => $validated['background_color'],
        ]);

        foreach ($validated['images'] as $index => $file) {
            if (!$file) {
                continue;
            }

            $position = $index + 1;

            $existingImage = $artwork->images()->where('position', $position)->first();

            $newPath = $file->store('artworks', 'public');

            if ($existingImage) {
                Storage::disk('public')->delete($existingImage->image_path);

                $existingImage->update([
                    'image_path' => $newPath,
                ]);
            } else {
                $artwork->images()->create([
                    'image_path' => $newPath,
                    'position' => $position,
                ]);
            }
        }

        return redirect()->route('admin.artworks.index');
    }

    public function destroy(Artwork $artwork)
    {
        foreach ($artwork->images as $image) {
            Storage::disk('public')->delete($image->image_path);
        }

        $artwork->delete();

        return redirect()->route('admin.artworks.index');
    }
}