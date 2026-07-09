<?php

namespace App\Services;

use App\Models\Destination;
use App\Models\Itinerary;
use App\Models\ItineraryActivity;
use App\Models\ItineraryDay;
use App\Models\User;
use Gemini\Data\GenerationConfig;
use Gemini\Enums\ResponseMimeType;
use Gemini\Laravel\Facades\Gemini;
use Illuminate\Support\Facades\DB;

class ItineraryGeneratorService
{
    public function __construct(
        private GooglePlacesService $placesService,
        private AttractionPriceService $priceService,
        private CostEnrichmentService $costService,
    ) {}

    public function generate(User $user, array $params): Itinerary
    {
        set_time_limit((int) config('itinerary.generation_time_limit', 120));

        $aiData = $this->callGemini($params);

        return DB::transaction(function () use ($user, $params, $aiData) {
            $itinerary = Itinerary::create([
                'user_id' => $user->id,
                'title' => $aiData['title'] ?? "{$params['duration_days']}-Day Trip to {$params['location']}",
                'location' => $params['location'],
                'country' => config('itinerary.country'),
                'currency' => config('itinerary.currency'),
                'duration_days' => $params['duration_days'],
                'budget_min' => $params['budget_min'] ?? null,
                'budget_max' => $params['budget_max'] ?? null,
                'activity_preferences' => $params['activity_preferences'],
                'travel_style' => $params['travel_style'] ?? 'mid-range',
                'pace' => $params['pace'] ?? 'moderate',
                'start_date' => $params['start_date'] ?? null,
                'status' => 'generated',
                'summary' => $aiData['summary'] ?? null,
                'tips' => $aiData['tips'] ?? [],
                'budget_breakdown' => $aiData['budget_breakdown'] ?? null,
                'ai_prompt_hash' => hash('sha256', json_encode($params)),
            ]);

            foreach ($aiData['days'] ?? [] as $dayData) {
                $day = ItineraryDay::create([
                    'itinerary_id' => $itinerary->id,
                    'day_number' => $dayData['day_number'],
                    'title' => $dayData['title'] ?? "Day {$dayData['day_number']}",
                    'notes' => $dayData['notes'] ?? null,
                    'date' => isset($params['start_date'])
                        ? now()->parse($params['start_date'])->addDays($dayData['day_number'] - 1)->toDateString()
                        : null,
                ]);

                foreach ($dayData['activities'] ?? [] as $index => $activityData) {
                    ItineraryActivity::create([
                        'itinerary_day_id' => $day->id,
                        'sort_order' => $index,
                        'name' => $activityData['name'],
                        'description' => $activityData['description'] ?? null,
                        'start_time' => $this->normalizeTime($activityData['start_time'] ?? null),
                        'end_time' => $this->normalizeTime($activityData['end_time'] ?? null),
                        'duration_minutes' => $activityData['duration_minutes'] ?? null,
                        'category' => $activityData['category'] ?? 'other',
                        'estimated_cost' => $activityData['estimated_cost_myr'] ?? 0,
                        'cost_source' => 'ai',
                        'search_query' => $activityData['search_query'] ?? "{$activityData['name']} {$params['location']}",
                        'is_ai_suggested' => true,
                    ]);
                }
            }

            $itinerary->load('days.activities');
            $this->placesService->enrichItinerary($itinerary);
            $this->priceService->applyToItinerary($itinerary);
            $this->costService->recalculateItinerary($itinerary);

            return $itinerary->fresh(['days.activities']);
        });
    }

    /**
     * Fast first step of progressive generation: one small Gemini call that
     * produces only the trip outline (title, summary, day themes, tips).
     * Day rows are created without activities and the itinerary is left in
     * 'generating' status so the frontend can fill days in one by one.
     */
    public function generateOutline(User $user, array $params): Itinerary
    {
        set_time_limit((int) config('itinerary.generation_time_limit', 120));

        $aiData = $this->generateJson($this->buildOutlinePrompt($params), 4096);

        return DB::transaction(function () use ($user, $params, $aiData) {
            $itinerary = Itinerary::create([
                'user_id' => $user->id,
                'title' => $aiData['title'] ?? "{$params['duration_days']}-Day Trip to {$params['location']}",
                'location' => $params['location'],
                'country' => config('itinerary.country'),
                'currency' => config('itinerary.currency'),
                'duration_days' => $params['duration_days'],
                'budget_min' => $params['budget_min'] ?? null,
                'budget_max' => $params['budget_max'] ?? null,
                'activity_preferences' => $params['activity_preferences'],
                'travel_style' => $params['travel_style'] ?? 'mid-range',
                'pace' => $params['pace'] ?? 'moderate',
                'start_date' => $params['start_date'] ?? null,
                'status' => 'generating',
                'summary' => $aiData['summary'] ?? null,
                'tips' => $aiData['tips'] ?? [],
                'budget_breakdown' => $aiData['budget_breakdown'] ?? null,
                'ai_prompt_hash' => hash('sha256', json_encode($params)),
            ]);

            $outlineDays = collect($aiData['days'] ?? [])->keyBy('day_number');

            for ($dayNumber = 1; $dayNumber <= $params['duration_days']; $dayNumber++) {
                $outline = $outlineDays->get($dayNumber, []);

                ItineraryDay::create([
                    'itinerary_id' => $itinerary->id,
                    'day_number' => $dayNumber,
                    'title' => $outline['title'] ?? "Day {$dayNumber}",
                    'notes' => $outline['notes'] ?? null,
                    'date' => isset($params['start_date'])
                        ? now()->parse($params['start_date'])->addDays($dayNumber - 1)->toDateString()
                        : null,
                ]);
            }

            return $itinerary->fresh(['days.activities']);
        });
    }

    /**
     * Second step of progressive generation: fill one day with activities.
     * Called once per day by the frontend so results appear batch by batch.
     */
    public function generateDayActivities(Itinerary $itinerary, int $dayNumber): ItineraryDay
    {
        set_time_limit((int) config('itinerary.generation_time_limit', 120));

        $day = $itinerary->days()->where('day_number', $dayNumber)->firstOrFail();

        $existingNames = $itinerary->activities()
            ->pluck('name')
            ->unique()
            ->values()
            ->all();

        $aiData = $this->generateJson($this->buildDayPrompt($itinerary, $day, $existingNames));
        $dayData = $aiData['days'][0] ?? $aiData;

        DB::transaction(function () use ($day, $dayData, $itinerary) {
            $day->activities()->delete();

            $day->update([
                'title' => $dayData['title'] ?? $day->title,
                'notes' => $dayData['notes'] ?? $day->notes,
            ]);

            foreach ($dayData['activities'] ?? [] as $index => $activityData) {
                ItineraryActivity::create([
                    'itinerary_day_id' => $day->id,
                    'sort_order' => $index,
                    'name' => $activityData['name'],
                    'description' => $activityData['description'] ?? null,
                    'start_time' => $this->normalizeTime($activityData['start_time'] ?? null),
                    'end_time' => $this->normalizeTime($activityData['end_time'] ?? null),
                    'duration_minutes' => $activityData['duration_minutes'] ?? null,
                    'category' => $activityData['category'] ?? 'other',
                    'estimated_cost' => $activityData['estimated_cost_myr'] ?? 0,
                    'cost_source' => 'ai',
                    'search_query' => $activityData['search_query'] ?? "{$activityData['name']} {$itinerary->location}",
                    'is_ai_suggested' => true,
                ]);
            }
        });

        $day->load('activities');
        $this->placesService->enrichDay($day, $itinerary->location);
        $this->priceService->applyToDay($day, $itinerary->location);
        $this->costService->recalculateItinerary($itinerary->fresh(['days.activities']));

        $this->markGeneratedIfComplete($itinerary);

        return $day->fresh('activities');
    }

    private function markGeneratedIfComplete(Itinerary $itinerary): void
    {
        $hasEmptyDays = $itinerary->days()
            ->whereDoesntHave('activities')
            ->exists();

        if (! $hasEmptyDays && $itinerary->status === 'generating') {
            $itinerary->update(['status' => 'generated']);
        }
    }

    public function regenerateDay(Itinerary $itinerary, int $dayNumber, ?string $notes = null): ItineraryDay
    {
        set_time_limit((int) config('itinerary.generation_time_limit', 120));

        $day = $itinerary->days()->where('day_number', $dayNumber)->firstOrFail();
        $aiData = $this->callGemini([
            'location' => $itinerary->location,
            'duration_days' => 1,
            'activity_preferences' => $itinerary->activity_preferences ?? [],
            'budget_min' => $itinerary->budget_min,
            'budget_max' => $itinerary->budget_max,
            'travel_style' => $itinerary->travel_style,
            'pace' => $itinerary->pace,
        ], $dayNumber, $notes, $itinerary);

        $dayData = $aiData['days'][0] ?? $aiData;

        DB::transaction(function () use ($day, $dayData, $itinerary) {
            $day->activities()->delete();

            $day->update([
                'title' => $dayData['title'] ?? $day->title,
                'notes' => $dayData['notes'] ?? $day->notes,
            ]);

            foreach ($dayData['activities'] ?? [] as $index => $activityData) {
                ItineraryActivity::create([
                    'itinerary_day_id' => $day->id,
                    'sort_order' => $index,
                    'name' => $activityData['name'],
                    'description' => $activityData['description'] ?? null,
                    'start_time' => $this->normalizeTime($activityData['start_time'] ?? null),
                    'end_time' => $this->normalizeTime($activityData['end_time'] ?? null),
                    'category' => $activityData['category'] ?? 'other',
                    'estimated_cost' => $activityData['estimated_cost_myr'] ?? 0,
                    'cost_source' => 'ai',
                    'search_query' => $activityData['search_query'] ?? "{$activityData['name']} {$itinerary->location}",
                    'is_ai_suggested' => true,
                ]);
            }
        });

        $day->load('activities');
        $itinerary = $itinerary->fresh(['days.activities']);
        $this->placesService->enrichItinerary($itinerary);
        $this->priceService->applyToItinerary($itinerary);
        $this->costService->recalculateItinerary($itinerary);

        return $day->fresh('activities');
    }

    public function suggestAlternatives(Itinerary $itinerary, ItineraryActivity $activity, ?string $context = null): array
    {
        $prompt = $this->buildSuggestionPrompt($itinerary, $activity, $context);
        $response = $this->generateJson($prompt);

        return $response['suggestions'] ?? [];
    }

    private function callGemini(array $params, ?int $singleDay = null, ?string $notes = null, ?Itinerary $existing = null): array
    {
        $prompt = $this->buildPrompt($params, $singleDay, $notes, $existing);

        return $this->generateJson($prompt);
    }

    private function generateJson(string $prompt, int $maxOutputTokens = 8192): array
    {
        $result = Gemini::generativeModel(model: config('itinerary.gemini_model'))
            ->withGenerationConfig(new GenerationConfig(
                responseMimeType: ResponseMimeType::APPLICATION_JSON,
                temperature: 0.7,
                maxOutputTokens: $maxOutputTokens,
            ))
            ->generateContent($prompt);

        $text = $result->text();

        \Log::info('Gemini Response:', [
            'response' => $text,
        ]);
        
        $data = json_decode($text, true);

        if (! is_array($data)) {
            throw new \RuntimeException('Gemini returned invalid JSON.');
        }

        return $data;
    }

    private function buildPrompt(array $params, ?int $singleDay = null, ?string $notes = null, ?Itinerary $existing = null): string
    {
        $preferences = implode(', ', $params['activity_preferences'] ?? []);
        $budgetText = $this->formatBudget($params);
        $travelStyle = $params['travel_style'] ?? 'mid-range';
        $pace = $params['pace'] ?? 'moderate';
        $featured = Destination::where('is_featured', true)->limit(5)->pluck('name')->implode(', ');
        $dayInstruction = $singleDay
            ? "Generate ONLY day {$singleDay} of the itinerary."
            : "Generate exactly {$params['duration_days']} days.";

        $extraNotes = $notes ? "User notes: {$notes}" : '';
        $existingContext = '';

        if ($existing) {
            $existingContext = "Existing trip title: {$existing->title}. Budget remaining context: RM{$existing->budget_max}.";
        }

        return <<<PROMPT
You are a Malaysia travel expert. Create a detailed travel itinerary in JSON only.

Requirements:
- Location: {$params['location']}, Malaysia
- {$dayInstruction}
- Interests: {$preferences}
- Travel style: {$travelStyle}
- Pace: {$pace}
- Budget: {$budgetText} (currency: MYR)
- Use realistic Malaysian locations, local food spots, and practical timing
- Featured destinations to consider: {$featured}
{$extraNotes}
{$existingContext}

Return JSON with this exact structure:
{
  "title": "string",
  "summary": "string",
  "days": [
    {
      "day_number": 1,
      "title": "string",
      "notes": "string",
      "activities": [
        {
          "name": "string",
          "description": "string",
          "category": "food|sightseeing|transport|accommodation|other",
          "start_time": "HH:MM",
          "end_time": "HH:MM",
          "duration_minutes": 60,
          "estimated_cost_myr": 25,
          "search_query": "place name for Google Places lookup"
        }
      ]
    }
  ],
  "budget_breakdown": {
    "accommodation": 0,
    "food": 0,
    "activities": 0,
    "transport": 0
  },
  "tips": ["string"]
}
PROMPT;
    }

    private function buildOutlinePrompt(array $params): string
    {
        $preferences = implode(', ', $params['activity_preferences'] ?? []);
        $budgetText = $this->formatBudget($params);
        $travelStyle = $params['travel_style'] ?? 'mid-range';
        $pace = $params['pace'] ?? 'moderate';
        $featured = Destination::where('is_featured', true)->limit(5)->pluck('name')->implode(', ');

        return <<<PROMPT
You are a Malaysia travel expert. Create a travel itinerary OUTLINE in JSON only. Do NOT include activities — only the trip title, summary, a short theme per day, budget breakdown, and tips.

Requirements:
- Location: {$params['location']}, Malaysia
- Exactly {$params['duration_days']} days
- Interests: {$preferences}
- Travel style: {$travelStyle}
- Pace: {$pace}
- Budget: {$budgetText} (currency: MYR)
- Featured destinations to consider: {$featured}

Return JSON with this exact structure:
{
  "title": "string",
  "summary": "string",
  "days": [
    {
      "day_number": 1,
      "title": "short theme for the day",
      "notes": "one-sentence description of the day"
    }
  ],
  "budget_breakdown": {
    "accommodation": 0,
    "food": 0,
    "activities": 0,
    "transport": 0
  },
  "tips": ["string"]
}
PROMPT;
    }

    private function buildDayPrompt(Itinerary $itinerary, ItineraryDay $day, array $existingNames): string
    {
        $preferences = implode(', ', $itinerary->activity_preferences ?? []);
        $budgetText = $this->formatBudget([
            'budget_min' => $itinerary->budget_min,
            'budget_max' => $itinerary->budget_max,
        ]);
        $avoid = $existingNames
            ? 'Do NOT repeat these places already used on other days: '.implode(', ', array_slice($existingNames, 0, 40))
            : '';

        return <<<PROMPT
You are a Malaysia travel expert. Create the detailed plan for ONE day of an existing trip, in JSON only.

Trip context:
- Trip: {$itinerary->title}
- Location: {$itinerary->location}, Malaysia
- Total duration: {$itinerary->duration_days} days
- Interests: {$preferences}
- Travel style: {$itinerary->travel_style}
- Pace: {$itinerary->pace}
- Budget for whole trip: {$budgetText} (currency: MYR)

Generate ONLY day {$day->day_number}, following this theme: "{$day->title}". {$day->notes}
{$avoid}
Use realistic Malaysian locations, local food spots, and practical timing.

Return JSON with this exact structure:
{
  "days": [
    {
      "day_number": {$day->day_number},
      "title": "string",
      "notes": "string",
      "activities": [
        {
          "name": "string",
          "description": "string",
          "category": "food|sightseeing|transport|accommodation|other",
          "start_time": "HH:MM",
          "end_time": "HH:MM",
          "duration_minutes": 60,
          "estimated_cost_myr": 25,
          "search_query": "place name for Google Places lookup"
        }
      ]
    }
  ]
}
PROMPT;
    }

    private function buildSuggestionPrompt(Itinerary $itinerary, ItineraryActivity $activity, ?string $context): string
    {
        $ctx = $context ?? 'Suggest similar alternatives nearby.';

        return <<<PROMPT
You are a Malaysia travel expert. Suggest 3 alternative activities to replace "{$activity->name}" in {$itinerary->location}.
Context: {$ctx}
Budget style: {$itinerary->travel_style}. Currency: MYR.

Return JSON only:
{
  "suggestions": [
    {
      "name": "string",
      "description": "string",
      "category": "food|sightseeing|transport|accommodation|other",
      "start_time": "HH:MM",
      "end_time": "HH:MM",
      "estimated_cost_myr": 25,
      "search_query": "string"
    }
  ]
}
PROMPT;
    }

    private function formatBudget(array $params): string
    {
        $min = $params['budget_min'] ?? null;
        $max = $params['budget_max'] ?? null;

        if ($min && $max) {
            return "RM{$min} - RM{$max}";
        }

        if ($max) {
            return "up to RM{$max}";
        }

        return 'flexible';
    }

    private function normalizeTime(?string $time): ?string
    {
        if (! $time) {
            return null;
        }

        if (preg_match('/^\d{2}:\d{2}$/', $time)) {
            return $time.':00';
        }

        return $time;
    }
}
