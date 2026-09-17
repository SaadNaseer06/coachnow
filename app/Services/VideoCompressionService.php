<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class VideoCompressionService
{
    /**
     * Compress an uploaded video to H.264 MP4 and store on the public disk.
     *
     * @return array{path: string, disk: string, original_bytes: int, stored_bytes: int, is_compressed: bool, thumbnail_path: ?string}
     */
    public function storeCompressed(UploadedFile $file, string $directory = 'shared-videos'): array
    {
        $originalBytes = (int) ($file->getSize() ?: 0);
        $disk = 'public';
        $tempDir = storage_path('app/tmp/videos');
        File::ensureDirectoryExists($tempDir);

        $inputPath = $tempDir.DIRECTORY_SEPARATOR.Str::uuid().'-source.'.$file->getClientOriginalExtension();
        $outputPath = $tempDir.DIRECTORY_SEPARATOR.Str::uuid().'-compressed.mp4';
        $thumbTemp = $tempDir.DIRECTORY_SEPARATOR.Str::uuid().'-thumb.jpg';

        try {
            if (! @copy($file->getRealPath(), $inputPath)) {
                throw new RuntimeException('Could not stage the uploaded video for compression.');
            }

            $ffmpeg = $this->resolveFfmpegBinary();
            $compressed = false;

            if ($ffmpeg) {
                // Streaming-friendly encode: H.264 + faststart so playback can begin
                // before the full file downloads. Cap resolution/bitrate for shared hosts.
                $result = Process::timeout(480)->run([
                    $ffmpeg,
                    '-y',
                    '-i', $inputPath,
                    '-c:v', 'libx264',
                    '-preset', 'veryfast',
                    '-profile:v', 'main',
                    '-level', '4.0',
                    '-pix_fmt', 'yuv420p',
                    '-crf', '28',
                    '-maxrate', '1800k',
                    '-bufsize', '3600k',
                    '-vf', "scale='min(1280,iw)':-2",
                    '-c:a', 'aac',
                    '-b:a', '96k',
                    '-ac', '2',
                    '-movflags', '+faststart',
                    $outputPath,
                ]);

                if ($result->successful() && is_file($outputPath) && filesize($outputPath) > 0) {
                    $compressed = true;
                }
            }

            $finalLocal = $compressed ? $outputPath : $inputPath;
            $extension = $compressed ? 'mp4' : strtolower($file->getClientOriginalExtension() ?: 'mp4');
            $uuid = (string) Str::uuid();
            $relativePath = trim($directory, '/').'/'.$uuid.'.'.$extension;

            $this->persistLocalFile($disk, $relativePath, $finalLocal);

            $thumbnailPath = null;
            if ($ffmpeg && $this->extractThumbnailFrame($ffmpeg, $finalLocal, $thumbTemp)) {
                $thumbnailPath = trim($directory, '/').'/thumbs/'.$uuid.'.jpg';
                $this->persistLocalFile($disk, $thumbnailPath, $thumbTemp);
            }

            $storedBytes = (int) Storage::disk($disk)->size($relativePath);
            if ($storedBytes < 1) {
                Storage::disk($disk)->delete($relativePath);
                throw new RuntimeException('Video file was not saved correctly. Please try again.');
            }

            return [
                'path' => $relativePath,
                'disk' => $disk,
                'original_bytes' => $originalBytes,
                'stored_bytes' => $storedBytes,
                'is_compressed' => $compressed,
                'thumbnail_path' => $thumbnailPath,
            ];
        } finally {
            @unlink($inputPath);
            @unlink($outputPath);
            @unlink($thumbTemp);
        }
    }

    /**
     * Store a JPEG/PNG thumbnail uploaded from the browser (FFmpeg fallback).
     */
    public function storeThumbnailImage(UploadedFile $file, string $directory = 'shared-videos/thumbs'): string
    {
        $mime = (string) $file->getMimeType();
        if (! in_array($mime, ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'], true)) {
            throw new RuntimeException('Thumbnail must be a JPEG or PNG image.');
        }

        $ext = $mime === 'image/png' ? 'png' : ($mime === 'image/webp' ? 'webp' : 'jpg');
        $relativePath = trim($directory, '/').'/'.Str::uuid().'.'.$ext;
        $this->persistLocalFile('public', $relativePath, $file->getRealPath());

        return $relativePath;
    }

    /**
     * Generate a thumbnail for an already-stored video file.
     */
    public function generateThumbnailForStoredVideo(string $videoPath, ?string $disk = 'public'): ?string
    {
        $diskName = $disk ?: 'public';
        $ffmpeg = $this->resolveFfmpegBinary();
        if (! $ffmpeg || ! Storage::disk($diskName)->exists($videoPath)) {
            return null;
        }

        $tempDir = storage_path('app/tmp/videos');
        File::ensureDirectoryExists($tempDir);

        $absoluteVideo = Storage::disk($diskName)->path($videoPath);
        $localVideo = $absoluteVideo;
        $copied = false;
        $thumbTemp = $tempDir.DIRECTORY_SEPARATOR.Str::uuid().'-thumb.jpg';

        try {
            if (! is_file($absoluteVideo)) {
                $ext = pathinfo($videoPath, PATHINFO_EXTENSION) ?: 'mp4';
                $localVideo = $tempDir.DIRECTORY_SEPARATOR.Str::uuid().'-source.'.$ext;
                file_put_contents($localVideo, Storage::disk($diskName)->get($videoPath));
                $copied = true;
            }

            if (! $this->extractThumbnailFrame($ffmpeg, $localVideo, $thumbTemp)) {
                return null;
            }

            $thumbnailPath = 'shared-videos/thumbs/'.pathinfo($videoPath, PATHINFO_FILENAME).'.jpg';
            $this->persistLocalFile($diskName, $thumbnailPath, $thumbTemp);

            return $thumbnailPath;
        } catch (\Throwable) {
            return null;
        } finally {
            if ($copied) {
                @unlink($localVideo);
            }
            @unlink($thumbTemp);
        }
    }

    /**
     * Grab a still frame from a local video using FFmpeg.
     */
    private function extractThumbnailFrame(string $ffmpeg, string $videoPath, string $thumbTemp): bool
    {
        $seeks = ['00:00:01', '00:00:00.5', '00:00:00'];

        foreach ($seeks as $ss) {
            @unlink($thumbTemp);

            $result = Process::timeout(60)->run([
                $ffmpeg,
                '-y',
                '-ss', $ss,
                '-i', $videoPath,
                '-frames:v', '1',
                '-an',
                '-q:v', '3',
                '-vf', 'scale=640:-2',
                $thumbTemp,
            ]);

            if ($result->successful() && is_file($thumbTemp) && filesize($thumbTemp) > 0) {
                return true;
            }
        }

        @unlink($thumbTemp);
        $fallback = Process::timeout(60)->run([
            $ffmpeg,
            '-y',
            '-i', $videoPath,
            '-frames:v', '1',
            '-an',
            '-q:v', '3',
            $thumbTemp,
        ]);

        return $fallback->successful() && is_file($thumbTemp) && filesize($thumbTemp) > 0;
    }

    public function deleteStored(?string $path, ?string $disk = 'public'): void
    {
        if (! $path) {
            return;
        }

        $diskName = $disk ?: 'public';
        if (Storage::disk($diskName)->exists($path)) {
            Storage::disk($diskName)->delete($path);
        }
    }

    /**
     * Copy a local file into the public disk and verify it landed on disk.
     */
    private function persistLocalFile(string $disk, string $relativePath, string $localPath): void
    {
        if (! is_file($localPath) || filesize($localPath) < 1) {
            throw new RuntimeException('Processed video file is missing or empty.');
        }

        $absolute = Storage::disk($disk)->path($relativePath);
        File::ensureDirectoryExists(dirname($absolute));

        if (! @copy($localPath, $absolute)) {
            throw new RuntimeException('Could not save the video to storage. Check disk permissions.');
        }

        clearstatcache(true, $absolute);

        if (! is_file($absolute) || filesize($absolute) < 1) {
            throw new RuntimeException('Video save verification failed. Please try again.');
        }

        @chmod($absolute, 0644);
    }

    public function isFfmpegAvailable(): bool
    {
        return $this->resolveFfmpegBinary() !== null;
    }

    public function resolveFfmpegBinary(): ?string
    {
        $configured = config('coachnow.ffmpeg_path') ?: env('FFMPEG_PATH');
        if ($configured && is_file($configured)) {
            return $configured;
        }

        $candidates = [
            'ffmpeg',
            'ffmpeg.exe',
            'C:\\ffmpeg\\bin\\ffmpeg.exe',
            'C:\\Program Files\\ffmpeg\\bin\\ffmpeg.exe',
            'C:\\Program Data\\chocolatey\\bin\\ffmpeg.exe',
        ];

        // winget Gyan.FFmpeg often lands under LocalAppData\Microsoft\WinGet\Packages
        $wingetRoot = getenv('LOCALAPPDATA')
            ? getenv('LOCALAPPDATA').'\\Microsoft\\WinGet\\Packages'
            : null;
        if ($wingetRoot && is_dir($wingetRoot)) {
            foreach (glob($wingetRoot.'\\Gyan.FFmpeg*\\ffmpeg-*\\bin\\ffmpeg.exe') ?: [] as $path) {
                $candidates[] = $path;
            }
        }

        foreach ($candidates as $binary) {
            if (str_contains($binary, DIRECTORY_SEPARATOR) || str_contains($binary, '/')) {
                if (is_file($binary)) {
                    return $binary;
                }

                continue;
            }

            try {
                $result = Process::timeout(5)->run([$binary, '-version']);
                if ($result->successful()) {
                    return $binary;
                }
            } catch (\Throwable) {
                // keep looking
            }
        }

        return null;
    }

    public function requireFfmpeg(): void
    {
        if (! $this->isFfmpegAvailable()) {
            throw new RuntimeException(
                'FFmpeg is required to compress uploaded videos. Install FFmpeg and set FFMPEG_PATH in .env if needed.'
            );
        }
    }
}
