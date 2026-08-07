<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GeneratedComposition;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class GeneratedCompositionController extends Controller
{
    public function index(): Response
    {
        $compositions = GeneratedComposition::query()
            ->with(['artwork:id,title', 'gallery:id,name'])
            ->latest()
            ->get()
            ->map(fn (GeneratedComposition $composition) => [
                'id' => $composition->id,
                'first_name' => $composition->first_name,
                'last_name' => $composition->last_name,
                'email' => $composition->email,
                'type' => $composition->artwork?->title ?? 'Tableau supprimé',
                'gallery' => $composition->gallery?->name,
                'created_at' => $composition->created_at?->timezone(config('app.timezone'))->format('d.m.Y H:i'),
            ]);

        return Inertia::render('Admin/GeneratedCompositions/Index', [
            'compositions' => $compositions,
        ]);
    }

    public function destroy(GeneratedComposition $generatedComposition): RedirectResponse
    {
        $generatedComposition->delete();

        return to_route('admin.generated-compositions.index');
    }
}
