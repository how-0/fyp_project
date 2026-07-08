<?php

namespace App\Services;

use App\Models\AttractionPrice;
use App\Models\Itinerary;
use App\Models\ItineraryActivity;
use App\Models\ItineraryDay;
use Illuminate\Support\Collection;

class AttractionPriceService
{
    /** @var Collection<int, AttractionPrice>|null */
    private ?Collection $catalog = null;

    public function applyToItinerary(Itinerary $itinerary): void
    {
        $itinerary->loadMissing('days.activities');

        foreach ($itinerary->days as $day) {
            $this->applyToDay($day, $itinerary->location);
        }
    }

    public function applyToDay(ItineraryDay $day, string $location): void
    {
        $day->loadMissing('activities');

        foreach ($day->activities as $activity) {
            if ($activity->cost_source === 'manual') {
                continue;
            }

            $match = $this->findMatch($activity, $location);

            if (! $match) {
                continue;
            }

            $activity->update([
                'estimated_cost' => $match->price_myr,
                'cost_source' => 'catalog',
                'attraction_price_id' => $match->id,
                'price_source_name' => $match->source_name,
                'price_source_url' => $match->source_url,
            ]);
        }
    }

    public function findMatch(ItineraryActivity $activity, string $location): ?AttractionPrice
    {
        $catalog = $this->activeCatalog();

        if ($activity->place_id) {
            $byPlaceId = $catalog->firstWhere('place_id', $activity->place_id);
            if ($byPlaceId) {
                return $byPlaceId;
            }
        }

        $stateHint = $this->extractStateHint($location);

        foreach ([$activity->name, $activity->search_query] as $candidate) {
            if (! $candidate) {
                continue;
            }

            $match = $this->matchByName($catalog, $candidate, $stateHint);
            if ($match) {
                return $match;
            }
        }

        return null;
    }

    private function activeCatalog(): Collection
    {
        if ($this->catalog === null) {
            $this->catalog = AttractionPrice::query()->active()->get();
        }

        return $this->catalog;
    }

    private function matchByName(Collection $catalog, string $needle, ?string $stateHint): ?AttractionPrice
    {
        $normalizedNeedle = $this->normalize($needle);

        if ($normalizedNeedle === '') {
            return null;
        }

        $candidates = $stateHint
            ? $catalog->filter(fn (AttractionPrice $row) => $this->stateMatches($row->state, $stateHint))
            : $catalog;

        if ($candidates->isEmpty()) {
            $candidates = $catalog;
        }

        foreach ($candidates as $row) {
            if ($this->namesMatch($normalizedNeedle, $this->normalize($row->name))) {
                return $row;
            }

            foreach ($row->aliases ?? [] as $alias) {
                if ($this->namesMatch($normalizedNeedle, $this->normalize($alias))) {
                    return $row;
                }
            }
        }

        return null;
    }

    private function namesMatch(string $needle, string $haystack): bool
    {
        if ($needle === '' || $haystack === '') {
            return false;
        }

        return $needle === $haystack
            || str_contains($needle, $haystack)
            || str_contains($haystack, $needle);
    }

    private function stateMatches(string $catalogState, string $stateHint): bool
    {
        $catalog = $this->normalize($catalogState);
        $hint = $this->normalize($stateHint);

        return $catalog === $hint
            || str_contains($catalog, $hint)
            || str_contains($hint, $catalog);
    }

    private function extractStateHint(string $location): ?string
    {
        $parts = array_map('trim', explode(',', $location));

        return $parts[1] ?? $parts[0] ?? null;
    }

    private function normalize(string $value): string
    {
        $value = strtolower($value);
        $value = preg_replace('/\bmalaysia\b/', '', $value) ?? $value;
        $value = preg_replace('/[^a-z0-9\s]/', ' ', $value) ?? $value;
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;

        return trim($value);
    }
}
