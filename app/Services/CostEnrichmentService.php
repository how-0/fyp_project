<?php

namespace App\Services;

use App\Models\Itinerary;
use App\Models\ItineraryActivity;

class CostEnrichmentService
{
    public function recalculateItinerary(Itinerary $itinerary): void
    {
        $total = 0;

        foreach ($itinerary->days as $day) {
            foreach ($day->activities as $activity) {
                if ($activity->cost_source !== 'manual') {
                    $resolved = $this->resolveActivityCost($activity);
                    $updates = ['estimated_cost' => $resolved['cost']];

                    if ($resolved['cost_source'] !== null) {
                        $updates['cost_source'] = $resolved['cost_source'];
                    }

                    $activity->update($updates);
                }

                $total += (float) $activity->fresh()->estimated_cost;
            }
        }

        $itinerary->update(['total_estimated_cost' => $total]);
    }

    /**
     * @return array{cost: float, cost_source: string|null}
     */
    public function resolveActivityCost(ItineraryActivity $activity): array
    {
        if ($activity->cost_source === 'manual') {
            return ['cost' => (float) $activity->estimated_cost, 'cost_source' => null];
        }

        if ($activity->cost_source === 'catalog') {
            return ['cost' => (float) $activity->estimated_cost, 'cost_source' => null];
        }

        if ($activity->price_level !== null) {
            $range = config("itinerary.price_level_ranges.{$activity->price_level}");

            if ($range) {
                return [
                    'cost' => ($range['min'] + $range['max']) / 2,
                    'cost_source' => 'places',
                ];
            }
        }

        return [
            'cost' => (float) $activity->estimated_cost,
            'cost_source' => 'ai',
        ];
    }

    public function budgetFitPercent(Itinerary $itinerary): ?float
    {
        if (! $itinerary->budget_max || $itinerary->budget_max <= 0) {
            return null;
        }

        return min(100, round(((float) $itinerary->total_estimated_cost / (float) $itinerary->budget_max) * 100, 1));
    }
}
