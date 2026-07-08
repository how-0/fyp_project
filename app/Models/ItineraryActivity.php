<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItineraryActivity extends Model
{
    protected $fillable = [
        'itinerary_day_id',
        'sort_order',
        'name',
        'description',
        'place_id',
        'lat',
        'lng',
        'address',
        'start_time',
        'end_time',
        'duration_minutes',
        'category',
        'estimated_cost',
        'cost_source',
        'attraction_price_id',
        'price_source_name',
        'price_source_url',
        'price_level',
        'is_ai_suggested',
        'user_modified',
        'search_query',
    ];

    protected function casts(): array
    {
        return [
            'estimated_cost' => 'decimal:2',
            'lat' => 'decimal:7',
            'lng' => 'decimal:7',
            'is_ai_suggested' => 'boolean',
            'user_modified' => 'boolean',
        ];
    }

    public function day(): BelongsTo
    {
        return $this->belongsTo(ItineraryDay::class, 'itinerary_day_id');
    }

    public function attractionPrice(): BelongsTo
    {
        return $this->belongsTo(AttractionPrice::class);
    }
}
