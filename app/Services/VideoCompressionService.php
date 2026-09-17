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
            if ($ffmpeg) {
                $thumbResult = Process::timeout(60)->run([
                    $ffmpeg,
                    '-y',
                    '-ss', '00:00:01',
                    '-i', $finalLocal,
                    '-frames:v', '1',
                    '-q:v', '3',
                    '-vf', "scale='min(640,iw)':-2",
                    $thumbTemp,
                ]);

                if ($thumbResult->successful() && is_file($thumbTemp) && filesize($thumbTemp) > 0) {
                    $thumbnailPath = trim($directory, '/').'/thumbs/'.$uuid.'.jpg';
                    $this->persistLocalFile($disk, $thumbnailPath, $thumbTemp);
                }
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

        $localVideo = $tempDir.DIRECTORY_SEPARATOR.Str::uuid().'-source'.pathinfo($videoPath, PATHINFO_EXTENSION);
        $thumbTemp = $tempDir.DIRECTORY_SEPARATOR.Str::uuid().'-thumb.jpg';

        try {
            file_put_contents($localVideo, Storage::disk($diskName)->get($videoPath));

            $thumbResult = Process::timeout(60)->run([
                $ffmpeg,
                '-y',
                '-ss', '00:00:01',
                '-i', $localVideo,
                '-frames:v', '1',
                '-q:v', '3',
                '-vf', "scale='min(640,iw)':-2",
                $thumbTemp,
            ]);

            if (! $thumbResult->successful() || ! is_file($thumbTemp) || filesize($thumbTemp) <= 0) {
                return null;
            }

            $thumbnailPath = 'shared-videos/thumbs/'.pathinfo($videoPath, PATHINFO_FILENAME).'.jpg';
            Storage::disk($diskName)->put($thumbnailPath, file_get_contents($thumbTemp));

            return $thumbnailPath;
        } finally {
            @unlink($localVideo);
            @unlink($thumbTemp);
        }
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
