<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GenerateItineraryRequest;
use App\Models\Itinerary;
use App\Models\ItineraryActivity;
use App\Services\CostEnrichmentService;
use App\Services\ItineraryGeneratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ItineraryController extends Controller
{
    public function __construct(
        private ItineraryGeneratorService $generator,
        private CostEnrichmentService $costService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $itineraries = Itinerary::forUser($request->user()->id)
            ->latest()
            ->get()
            ->map(fn (Itinerary $itinerary) => $this->summaryPayload($itinerary));

        return response()->json($itineraries);
    }

    public function generate(GenerateItineraryRequest $request): JsonResponse
    {
        $itinerary = $this->generator->generate($request->user(), $request->validated());

        return response()->json($this->fullPayload($itinerary), 201);
    }

    /**
     * Progressive generation, step 1: create the itinerary shell (title,
     * summary, day themes) with a fast Gemini call. Days have no activities
     * yet; the frontend fills them via generateDay below.
     */
    public function generateOutline(GenerateItineraryRequest $request): JsonResponse
    {
        $itinerary = $this->generator->generateOutline($request->user(), $request->validated());

        return response()->json($this->fullPayload($itinerary), 201);
    }

    /**
     * Progressive generation, step 2: generate activities for a single day.
     */
    public function generateDay(Request $request, Itinerary $itinerary): JsonResponse
    {
        $this->authorizeItinerary($request, $itinerary);

        $data = $request->validate([
            'day_number' => ['required', 'integer', 'min:1'],
        ]);

        $this->generator->generateDayActivities($itinerary, $data['day_number']);

        return response()->json($this->fullPayload($itinerary->fresh('days.activities')));
    }

    public function show(Request $request, Itinerary $itinerary): JsonResponse
    {
        $this->authorizeItinerary($request, $itinerary);

        return response()->json($this->fullPayload($itinerary->load('days.activities')));
    }

    public function update(Request $request, Itinerary $itinerary): JsonResponse
    {
        $this->authorizeItinerary($request, $itinerary);

        $data = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'status' => ['sometimes', 'in:draft,generated,finalized'],
        ]);

        $itinerary->update($data);

        return response()->json($this->fullPayload($itinerary->fresh('days.activities')));
    }

    public function destroy(Request $request, Itinerary $itinerary): JsonResponse
    {
        $this->authorizeItinerary($request, $itinerary);
        $itinerary->delete();

        return response()->json(['message' => 'Itinerary deleted.']);
    }

    public function duplicate(Request $request, Itinerary $itinerary): JsonResponse
    {
        $this->authorizeItinerary($request, $itinerary);
        $itinerary->load('days.activities');

        $copy = $itinerary->replicate(['ai_prompt_hash']);
        $copy->title = $itinerary->title.' (Copy)';
        $copy->status = 'draft';
        $copy->save();

        foreach ($itinerary->days as $day) {
            $newDay = $day->replicate();
            $newDay->itinerary_id = $copy->id;
            $newDay->save();

            foreach ($day->activities as $activity) {
                $newActivity = $activity->replicate();
                $newActivity->itinerary_day_id = $newDay->id;
                $newActivity->save();
            }
        }

        return response()->json($this->fullPayload($copy->fresh('days.activities')), 201);
    }

    public function regenerate(Request $request, Itinerary $itinerary): JsonResponse
    {
        $this->authorizeItinerary($request, $itinerary);

        $data = $request->validate([
            'day_number' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $this->generator->regenerateDay($itinerary, $data['day_number'], $data['notes'] ?? null);

        return response()->json($this->fullPayload($itinerary->fresh('days.activities')));
    }

    public function reorderActivities(Request $request, Itinerary $itinerary): JsonResponse
    {
        $this->authorizeItinerary($request, $itinerary);

        $data = $request->validate([
            'activities' => ['required', 'array'],
            'activities.*.id' => ['required', 'integer', 'exists:itinerary_activities,id'],
            'activities.*.day_id' => ['required', 'integer', 'exists:itinerary_days,id'],
            'activities.*.sort_order' => ['required', 'integer', 'min:0'],
        ]);

        foreach ($data['activities'] as $item) {
            $activity = ItineraryActivity::find($item['id']);
            $day = $itinerary->days()->where('id', $item['day_id'])->first();

            if (! $activity || ! $day || $activity->day->itinerary_id !== $itinerary->id) {
                continue;
            }

            $activity->update([
                'itinerary_day_id' => $item['day_id'],
                'sort_order' => $item['sort_order'],
                'user_modified' => true,
            ]);
        }

        return response()->json($this->fullPayload($itinerary->fresh('days.activities')));
    }

    public function updateActivity(Request $request, Itinerary $itinerary, ItineraryActivity $activity): JsonResponse
    {
        $this->authorizeItinerary($request, $itinerary);
        $this->authorizeActivity($itinerary, $activity);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'estimated_cost' => ['sometimes', 'numeric', 'min:0'],
            'category' => ['sometimes', 'string'],
        ]);

        if (isset($data['estimated_cost'])) {
            $data['cost_source'] = 'manual';
            $data['user_modified'] = true;
        } else {
            $data['user_modified'] = true;
        }

        $activity->update($data);
        $this->costService->recalculateItinerary($itinerary->fresh('days.activities'));

        return response()->json($this->fullPayload($itinerary->fresh('days.activities')));
    }

    public function destroyActivity(Request $request, Itinerary $itinerary, ItineraryActivity $activity): JsonResponse
    {
        $this->authorizeItinerary($request, $itinerary);
        $this->authorizeActivity($itinerary, $activity);

        $activity->delete();
        $this->costService->recalculateItinerary($itinerary->fresh('days.activities'));

        return response()->json($this->fullPayload($itinerary->fresh('days.activities')));
    }

    public function suggestAlternative(Request $request, Itinerary $itinerary, ItineraryActivity $activity): JsonResponse
    {
        $this->authorizeItinerary($request, $itinerary);
        $this->authorizeActivity($itinerary, $activity);

        $data = $request->validate([
            'context' => ['nullable', 'string', 'max:500'],
        ]);

        $suggestions = $this->generator->suggestAlternatives(
            $itinerary,
            $activity,
            $data['context'] ?? null
        );

        return response()->json(['suggestions' => $suggestions]);
    }

    public function compare(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:2', 'max:3'],
            'ids.*' => ['integer', 'exists:itineraries,id'],
        ]);

        $itineraries = Itinerary::forUser($request->user()->id)
            ->whereIn('id', $data['ids'])
            ->with('days.activities')
            ->get();

        $summaries = $itineraries->map(function (Itinerary $itinerary) {
            $activities = $itinerary->days->flatMap(fn ($day) => $day->activities);
            $categories = $activities->groupBy('category')->map->count();

            return [
                'id' => $itinerary->id,
                'title' => $itinerary->title,
                'location' => $itinerary->location,
                'duration_days' => $itinerary->duration_days,
                'total_estimated_cost' => $itinerary->total_estimated_cost,
                'budget_min' => $itinerary->budget_min,
                'budget_max' => $itinerary->budget_max,
                'budget_fit_percent' => $this->costService->budgetFitPercent($itinerary),
                'activity_count' => $activities->count(),
                'categories' => $categories,
                'status' => $itinerary->status,
            ];
        });

        $cheapest = $summaries->sortBy('total_estimated_cost')->first();
        $bestFit = $summaries->sortBy(fn ($s) => abs(($s['budget_fit_percent'] ?? 100) - 100))->first();

        return response()->json([
            'itineraries' => $summaries,
            'highlights' => [
                'cheapest_id' => $cheapest['id'] ?? null,
                'best_budget_fit_id' => $bestFit['id'] ?? null,
            ],
        ]);
    }

    private function authorizeItinerary(Request $request, Itinerary $itinerary): void
    {
        abort_if($itinerary->user_id !== $request->user()->id, 403);
    }

    private function authorizeActivity(Itinerary $itinerary, ItineraryActivity $activity): void
    {
        abort_if($activity->day->itinerary_id !== $itinerary->id, 403);
    }

    private function summaryPayload(Itinerary $itinerary): array
    {
        return [
            'id' => $itinerary->id,
            'title' => $itinerary->title,
            'location' => $itinerary->location,
            'duration_days' => $itinerary->duration_days,
            'total_estimated_cost' => (float) $itinerary->total_estimated_cost,
            'budget_min' => $itinerary->budget_min !== null ? (float) $itinerary->budget_min : null,
            'budget_max' => $itinerary->budget_max !== null ? (float) $itinerary->budget_max : null,
            'budget_fit_percent' => $this->costService->budgetFitPercent($itinerary),
            'status' => $itinerary->status,
            'created_at' => $itinerary->created_at,
        ];
    }

    private function fullPayload(Itinerary $itinerary): array
    {
        $itinerary->loadMissing('days.activities');

        return [
            ...$this->summaryPayload($itinerary),
            'currency' => $itinerary->currency,
            'activity_preferences' => $itinerary->activity_preferences,
            'travel_style' => $itinerary->travel_style,
            'pace' => $itinerary->pace,
            'start_date' => $itinerary->start_date,
            'summary' => $itinerary->summary,
            'tips' => $itinerary->tips,
            'budget_breakdown' => $itinerary->budget_breakdown,
            'days' => $itinerary->days->map(fn ($day) => [
                'id' => $day->id,
                'day_number' => $day->day_number,
                'title' => $day->title,
                'date' => $day->date,
                'notes' => $day->notes,
                'activities' => $day->activities->map(fn ($a) => [
                    'id' => $a->id,
                    'sort_order' => $a->sort_order,
                    'name' => $a->name,
                    'description' => $a->description,
                    'place_id' => $a->place_id,
                    'lat' => $a->lat,
                    'lng' => $a->lng,
                    'address' => $a->address,
                    'start_time' => $a->start_time ? substr((string) $a->start_time, 0, 5) : null,
                    'end_time' => $a->end_time ? substr((string) $a->end_time, 0, 5) : null,
                    'duration_minutes' => $a->duration_minutes,
                    'category' => $a->category,
                    'estimated_cost' => $a->estimated_cost,
                    'cost_source' => $a->cost_source,
                    'price_source_name' => $a->price_source_name,
                    'price_source_url' => $a->price_source_url,
                    'price_level' => $a->price_level,
                    'is_ai_suggested' => $a->is_ai_suggested,
                    'user_modified' => $a->user_modified,
                ]),
            ]),
        ];
    }
}
