<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Destination;
use Illuminate\Http\JsonResponse;

class DestinationController extends Controller
{
    public function featured(): JsonResponse
    {
        $destinations = Destination::query()
            ->where('is_featured', true)
            ->orderBy('state')
            ->orderBy('name')
            ->get(['id', 'name', 'state', 'category', 'description', 'image_url']);

        return response()->json($destinations);
    }
}
