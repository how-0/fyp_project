<?php

use App\Http\Controllers\DebugController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DebugController::class, 'index']);
