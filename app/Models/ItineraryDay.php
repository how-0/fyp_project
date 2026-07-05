<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ItineraryDay extends Model
{
    protected $fillable = [
        'itinerary_id',
        'day_number',
        'date',
        'title',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public function itinerary(): BelongsTo
    {
        return $this->belongsTo(Itinerary::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(ItineraryActivity::class)->orderBy('sort_order');
    }
}
