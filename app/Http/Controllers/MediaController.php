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

    /**
     * Force-download a public-disk file (Content-Disposition: attachment).
     */
    public function download(Request $request, string $path): BinaryFileResponse
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

        $filename = basename($path);
        $downloadAs = $request->query('name');
        if (is_string($downloadAs) && $downloadAs !== '') {
            $safe = preg_replace('/[^A-Za-z0-9._\-\s]+/', '', $downloadAs) ?: $filename;
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            $filename = $ext && ! str_ends_with(strtolower($safe), '.'.$ext)
                ? $safe.'.'.$ext
                : $safe;
        }

        return response()->download($absolute, $filename, [
            'Cache-Control' => 'private, max-age=0, must-revalidate',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
