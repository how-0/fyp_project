<?php

namespace App\Services;

use App\Models\Itinerary;
use App\Models\ItineraryActivity;

class CostEnrichmentService
{
    public function recalculateItinerary(Itinerary $itinerary): void
    {
        $itinerary->loadMissing('days.activities');
        $total = 0;

        foreach ($itinerary->days as $day) {
            foreach ($day->activities as $activity) {
                if (! in_array($activity->cost_source, ['manual', 'scaled'], true)) {
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
     * Scale activity costs so the trip total falls within the user's budget range.
     */
    public function normalizeToBudgetRange(Itinerary $itinerary): void
    {
        $min = $itinerary->budget_min !== null ? (float) $itinerary->budget_min : null;
        $max = $itinerary->budget_max !== null ? (float) $itinerary->budget_max : null;

        if ($min === null && $max === null) {
            return;
        }

        $itinerary->loadMissing('days.activities');
        $total = $this->sumActivityCosts($itinerary);

        $floor = $min ?? 0.0;
        $ceiling = $max ?? $total;

        if ($total >= $floor && $total <= $ceiling) {
            return;
        }

        $target = $this->resolveBudgetTarget($min, $max);

        if ($total <= 0) {
            $this->distributeFlatCosts($itinerary, $target);
            $itinerary->update(['total_estimated_cost' => $target]);

            return;
        }

        $factor = $target / $total;
        $this->scaleActivityCosts($itinerary, $factor);

        $newTotal = $this->sumActivityCosts($itinerary);

        if ($min !== null && $newTotal < $min) {
            $this->topUpCosts($itinerary, $min - $newTotal);
            $newTotal = $this->sumActivityCosts($itinerary);
        }

        if ($max !== null && $newTotal > $max) {
            $this->scaleActivityCosts($itinerary, $max / $newTotal);
            $newTotal = $this->sumActivityCosts($itinerary);
        }

        $itinerary->update(['total_estimated_cost' => round($newTotal, 2)]);
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

    private function resolveBudgetTarget(?float $min, ?float $max): float
    {
        if ($min !== null && $max !== null) {
            return ($min + $max) / 2;
        }

        if ($max !== null) {
            return $max * 0.9;
        }

        return $min ?? 0.0;
    }

    private function sumActivityCosts(Itinerary $itinerary): float
    {
        return (float) $itinerary->days
            ->flatMap(fn ($day) => $day->activities)
            ->sum(fn ($activity) => (float) $activity->estimated_cost);
    }

    private function scaleActivityCosts(Itinerary $itinerary, float $factor): void
    {
        foreach ($itinerary->days as $day) {
            foreach ($day->activities as $activity) {
                if ($activity->cost_source === 'manual') {
                    continue;
                }

                $activity->update([
                    'estimated_cost' => round(max(0, (float) $activity->estimated_cost * $factor), 2),
                    'cost_source' => 'scaled',
                ]);
            }
        }
    }

    private function topUpCosts(Itinerary $itinerary, float $gap): void
    {
        $candidates = $itinerary->days
            ->flatMap(fn ($day) => $day->activities)
            ->filter(fn ($activity) => $activity->cost_source !== 'manual')
            ->sortByDesc(fn ($activity) => in_array($activity->category, ['food', 'sightseeing'], true) ? 1 : 0)
            ->values();

        if ($candidates->isEmpty()) {
            return;
        }

        $perActivity = round($gap / $candidates->count(), 2);
        $remaining = $gap;

        foreach ($candidates as $index => $activity) {
            $add = $index === $candidates->count() - 1
                ? round($remaining, 2)
                : $perActivity;

            $activity->update([
                'estimated_cost' => round((float) $activity->estimated_cost + $add, 2),
                'cost_source' => 'scaled',
            ]);

            $remaining -= $add;
        }
    }

    private function distributeFlatCosts(Itinerary $itinerary, float $target): void
    {
        $activities = $itinerary->days->flatMap(fn ($day) => $day->activities);

        if ($activities->isEmpty()) {
            return;
        }

        $perActivity = round($target / $activities->count(), 2);
        $remaining = $target;

        foreach ($activities->values() as $index => $activity) {
            if ($activity->cost_source === 'manual') {
                $remaining -= (float) $activity->estimated_cost;

                continue;
            }

            $cost = $index === $activities->count() - 1
                ? round(max(0, $remaining), 2)
                : $perActivity;

            $activity->update([
                'estimated_cost' => $cost,
                'cost_source' => 'scaled',
            ]);

            $remaining -= $cost;
        }
    }
}
