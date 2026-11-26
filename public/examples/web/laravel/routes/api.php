<?php

use App\Http\Controllers\TimeEntryController;
use App\Http\Middleware\IdempotencyKey;
use Illuminate\Support\Facades\Route;

// Each domain registers its own routes — the middleware stack per
// route is the contract declaration.
Route::middleware(['auth:sanctum', IdempotencyKey::class])->group(function () {
    // throttle:N,1 = N requests/min per user — writes get a tighter budget.
    Route::get('time-entries', [TimeEntryController::class, 'index'])
        ->middleware('throttle:60,1');
    Route::post('time-entries', [TimeEntryController::class, 'store'])
        ->middleware('throttle:10,1');
});
