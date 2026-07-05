<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlacesCache extends Model
{
    protected $table = 'places_cache';

    protected $fillable = [
        'place_id',
        'name',
        'lat',
        'lng',
        'address',
        'price_level',
        'types',
        'cached_at',
    ];

    protected function casts(): array
    {
        return [
            'types' => 'array',
            'lat' => 'decimal:7',
            'lng' => 'decimal:7',
            'cached_at' => 'datetime',
        ];
    }
}
