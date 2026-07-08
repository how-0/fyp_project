<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Itinerary extends Model
{
    use CrudTrait;

    protected $fillable = [
        'user_id',
        'title',
        'location',
        'country',
        'currency',
        'duration_days',
        'budget_min',
        'budget_max',
        'activity_preferences',
        'travel_style',
        'pace',
        'start_date',
        'status',
        'total_estimated_cost',
        'budget_breakdown',
        'summary',
        'tips',
        'ai_prompt_hash',
    ];

    protected function casts(): array
    {
        return [
            'activity_preferences' => 'array',
            'budget_breakdown' => 'array',
            'tips' => 'array',
            'budget_min' => 'decimal:2',
            'budget_max' => 'decimal:2',
            'total_estimated_cost' => 'decimal:2',
            'start_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function days(): HasMany
    {
        return $this->hasMany(ItineraryDay::class)->orderBy('day_number');
    }

    public function activities(): HasManyThrough
    {
        return $this->hasManyThrough(ItineraryActivity::class, ItineraryDay::class);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
