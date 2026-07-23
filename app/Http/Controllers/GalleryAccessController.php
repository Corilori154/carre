<?php

namespace App\Http\Controllers;

use App\Models\Gallery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;

class GalleryAccessController extends Controller
{
    public function create(Request $request, Gallery $gallery)
    {
        return Inertia::render('Public/GalleryAccess', [
            'gallery' => $gallery->only('name', 'slug'),
            'redirect' => $this->safeRedirect($request, $gallery),
            'isClaimed' => (bool) $gallery->claimed_at,
            'isConfigured' => (bool) $gallery->access_password,
            'isExpired' => $gallery->access_password_expires_at?->isPast() ?? false,
        ]);
    }

    public function store(Request $request, Gallery $gallery)
    {
        $validated = $request->validate([
            'password' => ['required', 'string'],
            'redirect' => ['nullable', 'string'],
        ]);

        $token = Str::random(64);
        $claimed = DB::transaction(function () use ($gallery, $validated, $token) {
            $lockedGallery = Gallery::whereKey($gallery->id)->lockForUpdate()->firstOrFail();

            if ($lockedGallery->claimed_at || ! $lockedGallery->access_password) {
                return false;
            }

            if ($lockedGallery->access_password_expires_at?->isPast()) {
                return 'expired';
            }

            if (! Hash::check($validated['password'], $lockedGallery->access_password)) {
                return null;
            }

            $lockedGallery->forceFill([
                'device_token_hash' => hash('sha256', $token),
                'claimed_at' => now(),
            ])->save();

            return true;
        });

        if ($claimed === null) {
            return back()->withErrors(['password' => 'Le mot de passe est incorrect.']);
        }

        if ($claimed === 'expired') {
            return back()->withErrors(['password' => 'Ce mot de passe a expiré. Demandez-en un nouveau.']);
        }

        if ($claimed === false) {
            return back()->withErrors(['password' => 'Ce mot de passe a déjà été utilisé sur un autre appareil.']);
        }

        Cookie::queue(cookie(
            $gallery->accessCookieName(),
            $token,
            60 * 24 * 365 * 5,
            '/'.$gallery->slug,
            null,
            $request->isSecure(),
            true,
            false,
            'lax',
        ));

        return redirect()->to($this->safeRedirect($request, $gallery));
    }

    private function safeRedirect(Request $request, Gallery $gallery): string
    {
        $fallback = route('galleries.gallery', $gallery);
        $redirect = $request->input('redirect', $fallback);

        if (! is_string($redirect)) {
            return $fallback;
        }

        $path = parse_url($redirect, PHP_URL_PATH);

        if (! in_array($path, [
            '/'.$gallery->slug.'/gallery',
            '/'.$gallery->slug.'/compose-your-artwork',
        ], true)) {
            return $fallback;
        }

        return url($path);
    }
}
