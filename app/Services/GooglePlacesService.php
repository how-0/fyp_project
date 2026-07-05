<?php

namespace App\Services;

use App\Models\Itinerary;
use App\Models\PlacesCache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GooglePlacesService
{
    public function enrichItinerary(Itinerary $itinerary): void
    {
        foreach ($itinerary->days as $day) {
            foreach ($day->activities as $activity) {
                if (! $activity->search_query) {
                    continue;
                }

                $place = $this->lookupPlace($activity->search_query, $itinerary->location);

                if (! $place) {
                    continue;
                }

                $activity->update([
                    'place_id' => $place['place_id'],
                    'lat' => $place['lat'],
                    'lng' => $place['lng'],
                    'address' => $place['address'],
                    'price_level' => $place['price_level'],
                ]);
            }
        }
    }

    public function lookupPlace(string $query, string $location): ?array
    {
        $cached = PlacesCache::where('name', $query)->first();

        if ($cached && $cached->cached_at->gt(now()->subDays(config('itinerary.places_cache_days')))) {
            return [
                'place_id' => $cached->place_id,
                'name' => $cached->name,
                'lat' => $cached->lat,
                'lng' => $cached->lng,
                'address' => $cached->address,
                'price_level' => $cached->price_level,
            ];
        }

        $apiKey = config('services.google.places_api_key');

        if (! $apiKey) {
            return null;
        }

        try {
            $findResponse = Http::get('https://maps.googleapis.com/maps/api/place/findplacefromtext/json', [
                'input' => "{$query}, {$location}, Malaysia",
                'inputtype' => 'textquery',
                'fields' => 'place_id,name,geometry,formatted_address',
                'key' => $apiKey,
            ]);

            $candidate = $findResponse->json('candidates.0');

            if (! $candidate) {
                return null;
            }

            $detailsResponse = Http::get('https://maps.googleapis.com/maps/api/place/details/json', [
                'place_id' => $candidate['place_id'],
                'fields' => 'place_id,name,geometry,formatted_address,price_level,types',
                'key' => $apiKey,
            ]);

            $result = $detailsResponse->json('result');

            if (! $result) {
                return null;
            }

            $place = [
                'place_id' => $result['place_id'],
                'name' => $result['name'] ?? $query,
                'lat' => $result['geometry']['location']['lat'] ?? null,
                'lng' => $result['geometry']['location']['lng'] ?? null,
                'address' => $result['formatted_address'] ?? null,
                'price_level' => $result['price_level'] ?? null,
            ];

            PlacesCache::updateOrCreate(
                ['place_id' => $place['place_id']],
                [
                    'name' => $place['name'],
                    'lat' => $place['lat'],
                    'lng' => $place['lng'],
                    'address' => $place['address'],
                    'price_level' => $place['price_level'],
                    'types' => $result['types'] ?? [],
                    'cached_at' => now(),
                ]
            );

            return $place;
        } catch (\Throwable $e) {
            Log::warning('Google Places lookup failed', ['query' => $query, 'error' => $e->getMessage()]);

            return null;
        }
    }
}
