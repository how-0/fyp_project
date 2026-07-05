<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateItineraryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'location' => ['required', 'string', 'max:255'],
            'duration_days' => ['required', 'integer', 'min:1', 'max:14'],
            'activity_preferences' => ['required', 'array', 'min:1'],
            'activity_preferences.*' => [Rule::in(config('itinerary.activity_preferences'))],
            'budget_min' => ['nullable', 'numeric', 'min:0'],
            'budget_max' => ['nullable', 'numeric', 'gte:budget_min'],
            'travel_style' => ['nullable', Rule::in(config('itinerary.travel_styles'))],
            'pace' => ['nullable', Rule::in(config('itinerary.paces'))],
            'start_date' => ['nullable', 'date'],
        ];
    }
}
