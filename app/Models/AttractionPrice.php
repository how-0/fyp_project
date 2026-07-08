<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttractionPrice extends Model
{
    use CrudTrait;

    protected $fillable = [
        'name',
        'state',
        'place_id',
        'aliases',
        'category',
        'price_myr',
        'price_label',
        'source_name',
        'source_url',
        'price_as_of',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'aliases' => 'array',
            'price_myr' => 'decimal:2',
            'price_as_of' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function activities(): HasMany
    {
        return $this->hasMany(ItineraryActivity::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
