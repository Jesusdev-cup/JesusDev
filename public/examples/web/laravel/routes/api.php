<?php

use App\Http\Controllers\TimeEntryController;
use App\Http\Middleware\IdempotencyKey;
use Illuminate\Support\Facades\Route;

// Each domain registers its own routes — the middleware stack per
// route is the contract declaration.
Route::middleware(['auth:sanctum', IdempotencyKey::class])->group(function () {
    Route::get('time-entries', [TimeEntryController::class, 'index']);
    Route::post('time-entries', [TimeEntryController::class, 'store']);
});
