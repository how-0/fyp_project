<?php

return [
    'country' => 'MY',
    'currency' => 'MYR',
    'currency_symbol' => 'RM',

    'activity_preferences' => [
        'food',
        'heritage',
        'nature',
        'beaches',
        'shopping',
        'nightlife',
    ],

    'travel_styles' => [
        'budget',
        'mid-range',
        'luxury',
    ],

    'paces' => [
        'relaxed',
        'moderate',
        'packed',
    ],

    'categories' => [
        'food',
        'sightseeing',
        'transport',
        'accommodation',
        'other',
    ],

    'price_level_ranges' => [
        0 => ['min' => 5, 'max' => 15],
        1 => ['min' => 15, 'max' => 40],
        2 => ['min' => 40, 'max' => 80],
        3 => ['min' => 80, 'max' => 150],
        4 => ['min' => 150, 'max' => 300],
    ],

    'places_cache_days' => 7,

    'gemini_model' => env('GEMINI_MODEL', 'gemini-2.5-flash'),
];
