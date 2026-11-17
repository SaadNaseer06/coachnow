<?php

namespace App\Support;

use App\Models\Coach;
use App\Models\Location;
use App\Services\NominatimGeocoder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

/**
 * A coach can pick an admin park or type in their own venue ("Other location").
 */
final class CoachLocation
{
    public const OTHER = 'other';

    /**
     * Turn "Other location" into a saved location the coach owns, then point the request at it.
     * The same name and address is reused, so the coach's list doesn't fill with copies.
     */
    public static function resolve(Request $request, Coach $coach): void
    {
        if ($request->input('location_id') !== self::OTHER) {
            return;
        }

        $data = $request->validate([
            'new_location_name' => ['required', 'string', 'max:120'],
            'new_location_address' => ['required', 'string', 'min:8', 'max:255', 'regex:/\d/'],
        ], [
            'new_location_name.required' => 'Enter the park or venue name.',
            'new_location_address.required' => 'Enter the full address so families can find it.',
            'new_location_address.min' => 'Enter the full address, e.g. 31155 Pauba Rd, Temecula, CA 92592.',
            'new_location_address.regex' => 'Enter the full street address, including the number, e.g. 31155 Pauba Rd, Temecula, CA 92592.',
        ]);

        $name = trim($data['new_location_name']);
        $address = trim($data['new_location_address']);

        $location = Location::query()
            ->where('coach_id', $coach->id)
            ->where('name', $name)
            ->where('area', $address)
            ->first();

        if (! $location) {
            $coords = null;
            try {
                $coords = app(NominatimGeocoder::class)->geocode($address);
            } catch (\Throwable) {
                // Without coordinates the address still shows; only distance sorting is lost.
            }

            $location = Location::query()->create([
                'name' => $name,
                'slug' => self::uniqueSlug($name, $coach),
                'area' => $address,
                'status' => 'coach',
                'coach_id' => $coach->id,
                'latitude' => $coords['lat'] ?? null,
                'longitude' => $coords['lng'] ?? null,
            ]);
        }

        $request->merge(['location_id' => $location->id]);
    }

    /** Admin parks, plus this coach's own venues. Never another coach's. */
    public static function rule(Coach $coach): Exists
    {
        return Rule::exists('locations', 'id')->where(function ($query) use ($coach) {
            $query->where('status', 'live')->orWhere('coach_id', $coach->id);
        });
    }

    private static function uniqueSlug(string $name, Coach $coach): string
    {
        $base = Str::slug($name.' '.$coach->id) ?: 'venue-'.$coach->id;
        $slug = $base;
        $i = 2;
        while (Location::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
