<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Gallery;
use App\Models\Artwork;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class GalleryController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Galleries/Index', [
            'galleries' => Gallery::withCount('artworks')->latest()->get()->map(fn (Gallery $gallery) => [
                'id' => $gallery->id,
                'name' => $gallery->name,
                'slug' => $gallery->slug,
                'email' => $gallery->email,
                'gallery_url' => route('galleries.gallery', $gallery),
                'composer_url' => route('galleries.compose-artwork', $gallery),
                'is_configured' => (bool) $gallery->access_password,
                'is_claimed' => (bool) $gallery->claimed_at,
                'claimed_at' => $gallery->claimed_at?->format('d.m.Y H:i'),
                'artworks_count' => $gallery->artworks_count,
            ]),
        ]);
    }

    public function edit(Gallery $gallery)
    {
        $selectedArtworkIds = $gallery->artworks()->pluck('artworks.id');

        return Inertia::render('Admin/Galleries/Edit', [
            'gallery' => $gallery->only('name', 'slug'),
            'artworks' => Artwork::latest()
                ->get()
                ->map(fn (Artwork $artwork) => [
                    'id' => $artwork->id,
                    'title' => $artwork->title,
                    'is_public' => $artwork->is_public,
                    'selected' => $selectedArtworkIds->contains($artwork->id),
                ]),
        ]);
    }

    public function updateArtworks(Request $request, Gallery $gallery)
    {
        $validated = $request->validate([
            'artwork_ids' => ['present', 'array'],
            'artwork_ids.*' => ['integer', 'distinct', Rule::exists('artworks', 'id')],
        ]);

        $gallery->artworks()->sync($validated['artwork_ids']);

        return redirect()->route('admin.galleries.edit', $gallery)
            ->with('success', 'Les tableaux visibles ont été mis à jour.');
    }

    public function store(Request $request)
    {
        $request->merge([
            'slug' => Str::slug($request->input('slug') ?: $request->input('name')),
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:100', 'alpha_dash:ascii', Rule::unique('galleries')],
            'email' => ['nullable', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'max:255'],
        ]);

        Gallery::create([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'email' => $validated['email'] ?? null,
            'access_password' => $validated['password'],
        ]);

        return redirect()->route('admin.galleries.index')->with('success', 'La galerie a été créée.');
    }

    public function destroy(Gallery $gallery)
    {
        $gallery->delete();

        return redirect()->route('admin.galleries.index')->with('success', 'La galerie a été supprimée.');
    }

    public function resetAccess(Request $request, Gallery $gallery)
    {
        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8', 'max:255'],
        ]);

        $gallery->update([
            'access_password' => $validated['password'],
            'device_token_hash' => null,
            'claimed_at' => null,
        ]);

        return redirect()->route('admin.galleries.index')
            ->with('success', 'L’accès a été réinitialisé. Le nouveau mot de passe peut être utilisé une fois.');
    }
}
