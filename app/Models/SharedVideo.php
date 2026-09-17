<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class SharedVideo extends Model
{
    protected $fillable = [
        'coach_id',
        'athlete_id',
        'athlete_name',
        'title',
        'url',
        'source',
        'file_path',
        'thumbnail_path',
        'disk',
        'original_bytes',
        'stored_bytes',
        'is_compressed',
        'description',
        'duration_label',
        'skill_tag',
    ];

    protected function casts(): array
    {
        return [
            'is_compressed' => 'boolean',
            'original_bytes' => 'integer',
            'stored_bytes' => 'integer',
        ];
    }

    public function coach(): BelongsTo
    {
        return $this->belongsTo(Coach::class);
    }

    public function athlete(): BelongsTo
    {
        return $this->belongsTo(User::class, 'athlete_id');
    }

    public function scopeForAthlete(Builder $query, User $athlete): Builder
    {
        return $query->where(function (Builder $q) use ($athlete) {
            $q->where('athlete_id', $athlete->id)
                ->orWhere(function (Builder $inner) use ($athlete) {
                    $inner->whereNull('athlete_id')
                        ->whereRaw('LOWER(athlete_name) = ?', [mb_strtolower(trim($athlete->name))]);
                });
        });
    }

    public function scopeForPlayerProfile(Builder $query, array $profile): Builder
    {
        return $query->where(function (Builder $q) use ($profile) {
            if (! empty($profile['athlete_id'])) {
                $q->where('athlete_id', $profile['athlete_id']);
            }

            if (! empty($profile['name'])) {
                $q->orWhere(function (Builder $inner) use ($profile) {
                    $inner->whereNull('athlete_id')
                        ->whereRaw('LOWER(athlete_name) = ?', [mb_strtolower(trim((string) $profile['name']))]);
                });
            }
        });
    }

    public function isUpload(): bool
    {
        return ($this->source === 'upload') || filled($this->file_path);
    }

    public function playbackUrl(): string
    {
        if ($this->file_path) {
            return $this->publicMediaUrl($this->file_path);
        }

        return (string) $this->url;
    }

    public function thumbnailUrl(): ?string
    {
        $disk = $this->disk ?: 'public';

        if ($this->thumbnail_path) {
            if (Storage::disk($disk)->exists($this->thumbnail_path)) {
                return $this->publicMediaUrl($this->thumbnail_path);
            }

            $this->forceFill(['thumbnail_path' => null])->save();
        }

        if ($this->isUpload() && $this->file_path) {
            $generated = app(\App\Services\VideoCompressionService::class)
                ->generateThumbnailForStoredVideo($this->file_path, $disk);

            if ($generated) {
                $this->forceFill(['thumbnail_path' => $generated])->save();

                return $this->publicMediaUrl($generated);
            }
        }

        $url = (string) $this->url;
        if (preg_match('~(?:youtube\.com/watch\?v=|youtu\.be/)([A-Za-z0-9_-]{6,})~', $url, $m)) {
            return 'https://img.youtube.com/vi/'.$m[1].'/hqdefault.jpg';
        }

        return null;
    }

    public function metaLabel(): string
    {
        $sizeLabel = null;
        if ($this->stored_bytes) {
            $sizeLabel = $this->formatBytes((int) $this->stored_bytes);
            if ($this->is_compressed && $this->original_bytes && $this->original_bytes > $this->stored_bytes) {
                $sizeLabel .= ' compressed';
            }
        }

        return collect([
            $this->duration_label,
            $this->skill_tag,
            $sizeLabel,
            $this->coach?->display_name ? 'From '.$this->coach->display_name : null,
        ])->filter()->implode(' · ');
    }

    /**
     * @return array<string, mixed>
     */
    public function toDisplayArray(): array
    {
        $this->loadMissing('coach.user');

        return [
            'id' => $this->id,
            'title' => $this->title,
            'url' => $this->playbackUrl(),
            'thumbnail' => $this->thumbnailUrl(),
            'source' => $this->source ?: ($this->file_path ? 'upload' : 'url'),
            'is_upload' => $this->isUpload(),
            'is_compressed' => (bool) $this->is_compressed,
            'description' => $this->description,
            'meta' => $this->metaLabel() ?: ($this->description ?: 'Training video'),
            'duration_label' => $this->duration_label,
            'skill_tag' => $this->skill_tag,
            'coach' => $this->coach?->display_name,
            'shared_at' => $this->created_at?->diffForHumans() ?? '',
        ];
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes.' B';
        }

        if ($bytes < 1024 * 1024) {
            return round($bytes / 1024, 1).' KB';
        }

        return round($bytes / (1024 * 1024), 1).' MB';
    }

    private function publicMediaUrl(string $path): string
    {
        $path = ltrim(str_replace('\\', '/', $path), '/');

        // Always serve through /media — shared hosts often 404 /storage even when
        // the public disk symlink exists and PHP can see the file.
        return route('media.show', ['path' => $path], absolute: true);
    }
}
