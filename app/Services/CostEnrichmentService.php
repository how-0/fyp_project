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
                    $cost = $this->resolveActivityCost($activity);
                    $activity->update(['estimated_cost' => $cost]);
                }

                $total += (float) $activity->fresh()->estimated_cost;
            }
        }

        $itinerary->update(['total_estimated_cost' => $total]);
    }

    public function resolveActivityCost(ItineraryActivity $activity): float
    {
        if ($activity->cost_source === 'manual') {
            return (float) $activity->estimated_cost;
        }

        if ($activity->price_level !== null) {
            $range = config("itinerary.price_level_ranges.{$activity->price_level}");

            if ($range) {
                return ($range['min'] + $range['max']) / 2;
            }
        }

        return (float) $activity->estimated_cost;
    }

    public function budgetFitPercent(Itinerary $itinerary): ?float
    {
        if (! $itinerary->budget_max || $itinerary->budget_max <= 0) {
            return null;
        }

        return min(100, round(((float) $itinerary->total_estimated_cost / (float) $itinerary->budget_max) * 100, 1));
    }
}
