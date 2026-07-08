<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/itineraries', [\App\Http\Controllers\Api\ItineraryController::class, 'index']);
    Route::post('/itineraries/generate', [\App\Http\Controllers\Api\ItineraryController::class, 'generate']);
    Route::post('/itineraries/generate-outline', [\App\Http\Controllers\Api\ItineraryController::class, 'generateOutline']);
    Route::post('/itineraries/{itinerary}/generate-day', [\App\Http\Controllers\Api\ItineraryController::class, 'generateDay']);
    Route::post('/itineraries/compare', [\App\Http\Controllers\Api\ItineraryController::class, 'compare']);
    Route::get('/itineraries/{itinerary}', [\App\Http\Controllers\Api\ItineraryController::class, 'show']);
    Route::patch('/itineraries/{itinerary}', [\App\Http\Controllers\Api\ItineraryController::class, 'update']);
    Route::delete('/itineraries/{itinerary}', [\App\Http\Controllers\Api\ItineraryController::class, 'destroy']);
    Route::post('/itineraries/{itinerary}/duplicate', [\App\Http\Controllers\Api\ItineraryController::class, 'duplicate']);
    Route::post('/itineraries/{itinerary}/regenerate', [\App\Http\Controllers\Api\ItineraryController::class, 'regenerate']);
    Route::patch('/itineraries/{itinerary}/activities/reorder', [\App\Http\Controllers\Api\ItineraryController::class, 'reorderActivities']);
    Route::patch('/itineraries/{itinerary}/activities/{activity}', [\App\Http\Controllers\Api\ItineraryController::class, 'updateActivity']);
    Route::delete('/itineraries/{itinerary}/activities/{activity}', [\App\Http\Controllers\Api\ItineraryController::class, 'destroyActivity']);
    Route::post('/itineraries/{itinerary}/activities/{activity}/suggest', [\App\Http\Controllers\Api\ItineraryController::class, 'suggestAlternative']);
});