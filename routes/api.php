<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DestinationController;
use App\Http\Controllers\Api\ItineraryController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

Route::get('/destinations/featured', [DestinationController::class, 'featured']);

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/itineraries', [ItineraryController::class, 'index']);
    Route::post('/itineraries/generate', [ItineraryController::class, 'generate']);
    Route::post('/itineraries/generate-outline', [ItineraryController::class, 'generateOutline']);
    Route::post('/itineraries/{itinerary}/generate-day', [ItineraryController::class, 'generateDay']);
    Route::post('/itineraries/compare', [ItineraryController::class, 'compare']);
    Route::get('/itineraries/{itinerary}', [ItineraryController::class, 'show']);
    Route::patch('/itineraries/{itinerary}', [ItineraryController::class, 'update']);
    Route::delete('/itineraries/{itinerary}', [ItineraryController::class, 'destroy']);
    Route::post('/itineraries/{itinerary}/duplicate', [ItineraryController::class, 'duplicate']);
    Route::post('/itineraries/{itinerary}/regenerate', [ItineraryController::class, 'regenerate']);
    Route::patch('/itineraries/{itinerary}/activities/reorder', [ItineraryController::class, 'reorderActivities']);
    Route::patch('/itineraries/{itinerary}/activities/{activity}', [ItineraryController::class, 'updateActivity']);
    Route::delete('/itineraries/{itinerary}/activities/{activity}', [ItineraryController::class, 'destroyActivity']);
    Route::post('/itineraries/{itinerary}/activities/{activity}/suggest', [ItineraryController::class, 'suggestAlternative']);
});
