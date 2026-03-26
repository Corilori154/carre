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
        $artworks = Artwork::with('images')->latest()->get()->map(function ($artwork) {
            return [
                'id' => $artwork->id,
                'title' => $artwork->title,
                'background_color' => $artwork->background_color, // ✅ IMPORTANT
                'is_published' => $artwork->is_published,
                'images' => $artwork->images->map(function ($image) {
                    return [
                        'id' => $image->id,
                        'position' => $image->position,
                        'url' => Storage::url($image->image_path),
                    ];
                }),
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
            $path = $file->store('artworks');

            $artwork->images()->create([
                'image_path' => $path,
                'position' => $index + 1,
            ]); 
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