<?php

namespace App\Http\Middleware;

use App\Models\Gallery;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureGalleryDeviceAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Gallery $gallery */
        $gallery = $request->route('gallery');
        $token = $request->cookie($gallery->accessCookieName());

        if ($token && $gallery->device_token_hash && hash_equals(
            $gallery->device_token_hash,
            hash('sha256', $token),
        )) {
            return $next($request);
        }

        return redirect()->route('galleries.access', [
            'gallery' => $gallery,
            'redirect' => $request->fullUrl(),
        ]);
    }
}
