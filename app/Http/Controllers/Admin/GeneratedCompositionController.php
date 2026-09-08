<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GeneratedComposition;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class GeneratedCompositionController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim($request->validate(['search' => ['nullable', 'string', 'max:255']])['search'] ?? '');
        $compositions = GeneratedComposition::query()
            ->with(['artwork:id,title', 'artwork.images:id,artwork_id,image_path', 'gallery:id,name'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    foreach (preg_split('/\s+/u', $search) as $term) {
                        $query->where(function ($query) use ($term) {
                            $pattern = '%'.$term.'%';
                            $query->where('first_name', 'like', $pattern)
                                ->orWhere('last_name', 'like', $pattern)
                                ->orWhere('email', 'like', $pattern)
                                ->orWhereHas('artwork', fn ($query) => $query->where('title', 'like', $pattern))
                                ->orWhereHas('gallery', fn ($query) => $query->where('name', 'like', $pattern));
                        });
                    }
                });
            })
            ->latest()
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (GeneratedComposition $composition) => [
                'id' => $composition->id,
                'first_name' => $composition->first_name,
                'last_name' => $composition->last_name,
                'email' => $composition->email,
                'preview' => [
                    'background_color' => $composition->composition['background_color'] ?? '#f5f5f4',
                    'slots' => collect(range(0, 8))->map(function ($index) use ($composition) {
                        $slot = $composition->composition['slots'][$index] ?? null;
                        $image = $composition->artwork?->images->firstWhere('id', $slot['image_id'] ?? null);

                        return [
                            'url' => $image ? Storage::disk('public')->url($image->image_path) : null,
                            'rotation' => $slot['rotation'] ?? 0,
                            'missing' => $slot !== null && $image === null,
                        ];
                    }),
                ],
                'type' => $composition->artwork?->title ?? 'Tableau supprimé',
                'gallery' => $composition->gallery?->name,
                'created_at' => $composition->created_at?->timezone(config('app.timezone'))->format('d.m.Y H:i'),
            ]);

        return Inertia::render('Admin/GeneratedCompositions/Index', [
            'compositions' => $compositions,
            'filters' => ['search' => $search],
        ]);
    }

    public function destroy(GeneratedComposition $generatedComposition): RedirectResponse
    {
        $generatedComposition->delete();

        return to_route('admin.generated-compositions.index');
    }
}
