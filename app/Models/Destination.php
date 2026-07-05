<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;

class Destination extends Model
{
    use CrudTrait;

    protected $fillable = [
        'name',
        'state',
        'lat',
        'lng',
        'category',
        'description',
        'image_url',
        'is_featured',
    ];

    protected function casts(): array
    {
        return [
            'lat' => 'decimal:7',
            'lng' => 'decimal:7',
            'is_featured' => 'boolean',
        ];
    }
}
