<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Coach extends Model
{
    /** @use HasFactory<\Database\Factories\CoachFactory> */
    use HasFactory;

    public const SPECIALTIES = [
        'Private Soccer Training',
        'Small Group Soccer',
        'Team Training',
        '1-on-1 Skills',
        'Futsal',
        'Performance & Speed',
        'Youth Development',
        'Clinics & Camps',
    ];

    public const EXPERIENCE_OPTIONS = [
        '1-3 years',
        '4-5 years',
        '6-7 years',
        '8+ years',
    ];

    public const AGE_OPTIONS = [
        'Ages 5-10',
        'Ages 6-12',
        'Ages 8-14',
        'Ages 10-16',
        'Ages 12-18',
        'All ages',
    ];

    protected $fillable = [
        'user_id',
        'location_id',
        'display_name',
        'specialty',
        'sport',
        'experience',
        'ages',
        'status',
        'rate',
        'rating',
        'reviews_count',
        'photo_path',
        'bio',
    ];

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:2',
            'rating' => 'decimal:1',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function requestedSessions(): HasMany
    {
        return $this->hasMany(SessionRequest::class, 'requested_coach_id');
    }

    public function hostedSessions(): HasMany
    {
        return $this->hasMany(SessionRequest::class, 'host_coach_id');
    }

    public function sharedVideos(): HasMany
    {
        return $this->hasMany(SharedVideo::class);
    }

    public function photoUrl(): string
    {
        $path = $this->photo_path ?: 'assets/Rectangle 8.png';

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        if (str_starts_with($path, 'assets/')) {
            return asset($path);
        }

        return asset('storage/'.$path);
    }

    public function roleLabel(): string
    {
        $specialty = trim((string) $this->specialty);

        if ($specialty === '') {
            $sport = trim((string) $this->sport);

            return $sport !== '' ? $sport.' Coach' : 'Coach';
        }

        if (str_contains(strtolower($specialty), 'coach')) {
            return $specialty;
        }

        return $specialty;
    }

    public function shortName(): string
    {
        $name = trim((string) $this->display_name);
        $stripped = trim((string) preg_replace('/^coach\s+/i', '', $name));

        return $stripped !== '' ? $stripped : ($name !== '' ? $name : 'Coach');
    }

    public function experienceHighlight(): string
    {
        $experience = trim((string) $this->experience);

        if (preg_match('/(\d+\+)/', $experience, $match)) {
            return $match[1];
        }

        if (preg_match('/(\d+)/', $experience, $match)) {
            return $match[1].'+';
        }

        return $experience !== '' ? $experience : '—';
    }

    public function privateRate(): int
    {
        return max(0, (int) round((float) $this->rate));
    }

    public function groupRate(): int
    {
        return max(20, (int) round($this->privateRate() * 0.67));
    }

    public function teamRate(): int
    {
        return max($this->privateRate(), (int) round($this->privateRate() * 3.3));
    }

    public function isProfileComplete(): bool
    {
        return $this->isReadyForListing()
            && filled($this->photo_path)
            && ! str_starts_with((string) $this->photo_path, 'assets/Rectangle');
    }

    /**
     * Minimum fields required before an admin can publish the coach on Find a Coach.
     */
    public function isReadyForListing(): bool
    {
        return filled($this->display_name)
            && filled($this->sport)
            && filled($this->specialty)
            && filled($this->experience)
            && filled($this->ages)
            && filled($this->location_id)
            && $this->rate !== null;
    }

    /**
     * @return list<string>
     */
    public function missingProfileFields(): array
    {
        $missing = [];

        if (! filled($this->display_name)) {
            $missing[] = 'Display name';
        }
        if (! filled($this->sport)) {
            $missing[] = 'Sport';
        }
        if (! filled($this->specialty)) {
            $missing[] = 'Specialty';
        }
        if (! filled($this->experience)) {
            $missing[] = 'Experience';
        }
        if (! filled($this->ages)) {
            $missing[] = 'Ages you train';
        }
        if (! filled($this->location_id)) {
            $missing[] = 'Primary park';
        }
        if ($this->rate === null) {
            $missing[] = 'Session rate';
        }
        if (! filled($this->photo_path) || str_starts_with((string) $this->photo_path, 'assets/Rectangle')) {
            $missing[] = 'Profile photo';
        }

        return $missing;
    }

    /**
     * @return list<string>
     */
    public function missingListingFields(): array
    {
        return array_values(array_filter(
            $this->missingProfileFields(),
            fn (string $field) => $field !== 'Profile photo'
        ));
    }

    /**
     * Tags used by Find a Coach client-side age filters.
     */
    public function ageFilterTags(): string
    {
        $ages = strtolower((string) $this->ages);

        if ($ages === '' || str_contains($ages, 'all')) {
            return 'youth-5-8 youth-9-12 teen adult';
        }

        $tags = [];
        if (preg_match('/\b([5-8])\b/', $ages) || str_contains($ages, '5-') || str_contains($ages, '5–')) {
            $tags[] = 'youth-5-8';
        }
        if (preg_match('/\b(9|10|11|12)\b/', $ages) || str_contains($ages, '6-12') || str_contains($ages, '6–12') || str_contains($ages, '8-14') || str_contains($ages, '8–14')) {
            $tags[] = 'youth-9-12';
        }
        if (preg_match('/\b(1[3-7]|teen)\b/', $ages) || str_contains($ages, '10-16') || str_contains($ages, '10–16') || str_contains($ages, '12-18') || str_contains($ages, '12–18')) {
            $tags[] = 'teen';
        }
        if (str_contains($ages, 'adult') || str_contains($ages, '18')) {
            $tags[] = 'adult';
        }

        return $tags === [] ? 'youth-5-8 youth-9-12 teen' : implode(' ', array_unique($tags));
    }

    /**
     * Occupied 60-minute windows from bookings and hosted/targeted requests.
     * Coaches do not publish open slots — this is the real busy data.
     *
     * @param  list<int>  $coachIds
     * @return array<int, list<array{date:string,time:?string,minutes:int}>>
     */
    public static function occupancyByCoachIds($coachIds): array
    {
        $ids = collect($coachIds)->map(fn ($id) => (int) $id)->filter()->unique()->values();
        if ($ids->isEmpty()) {
            return [];
        }

        $from = now()->toDateString();
        $to = now()->addDays(21)->toDateString();
        $rows = [];

        $bookings = Booking::query()
            ->whereIn('coach_id', $ids)
            ->where('status', '!=', 'cancelled')
            ->whereDate('session_date', '>=', $from)
            ->whereDate('session_date', '<=', $to)
            ->get(['coach_id', 'session_date', 'session_time', 'duration_minutes']);

        foreach ($bookings as $booking) {
            $rows[$booking->coach_id][] = [
                'date' => optional($booking->session_date)->toDateString(),
                'time' => $booking->session_time ? substr((string) $booking->session_time, 0, 5) : null,
                'minutes' => (int) ($booking->duration_minutes ?: 60),
            ];
        }

        $requests = SessionRequest::query()
            ->whereIn('status', ['open', 'hosted', 'awaiting_deposit', 'confirmed'])
            ->whereDate('session_date', '>=', $from)
            ->whereDate('session_date', '<=', $to)
            ->where(function ($query) use ($ids) {
                $query->whereIn('host_coach_id', $ids)
                    ->orWhereIn('requested_coach_id', $ids);
            })
            ->get(['host_coach_id', 'requested_coach_id', 'session_date', 'session_time']);

        foreach ($requests as $request) {
            $coachId = $request->host_coach_id ?: $request->requested_coach_id;
            if (! $coachId || ! $ids->contains((int) $coachId)) {
                continue;
            }

            $rows[$coachId][] = [
                'date' => optional($request->session_date)->toDateString(),
                'time' => $request->session_time ? substr((string) $request->session_time, 0, 5) : null,
                'minutes' => 60,
            ];
        }

        return $rows;
    }

    public function deleteStoredPhoto(): void
    {
        $path = (string) $this->photo_path;

        if ($path === '' || str_starts_with($path, 'assets/') || str_starts_with($path, 'http')) {
            return;
        }

        Storage::disk('public')->delete($path);
    }
}
