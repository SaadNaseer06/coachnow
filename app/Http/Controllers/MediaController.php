<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MediaController extends Controller
{
    /**
     * Stream a public-disk file with proper HTTP Range support.
     * BinaryFileResponse lets browsers seek/buffer without downloading the whole file first.
     */
    public function show(Request $request, string $path): BinaryFileResponse
    {
        $path = ltrim(str_replace('\\', '/', $path), '/');

        if ($path === '' || str_contains($path, '..')) {
            abort(404);
        }

        $disk = Storage::disk('public');

        if (! $disk->exists($path)) {
            abort(404);
        }

        $absolute = $disk->path($path);

        if (! is_file($absolute)) {
            abort(404);
        }

        $mime = $disk->mimeType($path) ?: (mime_content_type($absolute) ?: 'application/octet-stream');

        return response()->file($absolute, [
            'Content-Type' => $mime,
            'Cache-Control' => 'public, max-age=604800, immutable',
            'Accept-Ranges' => 'bytes',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
