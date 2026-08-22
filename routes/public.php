<?php

use App\Http\Controllers\Public\AppointmentController;
use App\Http\Controllers\Public\BlogController;
use App\Http\Controllers\Public\LeadController;
use App\Http\Controllers\Public\MenuController;
use App\Http\Controllers\Public\PageController;
use App\Http\Controllers\Public\SavingsSimulatorController;
use App\Http\Controllers\Public\StationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public API routes (prefix: /api/public)
|--------------------------------------------------------------------------
|
| Read-only API consumed by the 4 public websites. The site is resolved
| by its slug in the route ({site}) — simplest option; an X-Site-Key
| header check can be layered on later if needed.
|
| The only allowed writes are lead capture (POST {site}/leads) and
| booking an inspection (POST {site}/appointments).
|
*/

Route::prefix('{site:slug}')->group(function (): void {
    Route::get('menu', [MenuController::class, 'show']);
    Route::get('globals', [PageController::class, 'globals']);
    Route::get('pages/{pageSlug}', [PageController::class, 'show']);
    Route::get('savings-simulator', SavingsSimulatorController::class);
    Route::get('blog', [BlogController::class, 'index']);
    Route::get('blog/{postSlug}', [BlogController::class, 'show']);
    Route::get('stations', [StationController::class, 'index']);
    Route::get('appointments/availability', [AppointmentController::class, 'availability']);
    // Throttled: the only public write, otherwise an open mailbox for spam.
    Route::post('leads', [LeadController::class, 'store'])->middleware('throttle:10,1');
    // Booking an inspection is the second public write, throttled the same way.
    Route::post('appointments', [AppointmentController::class, 'store'])->middleware('throttle:10,1');
});
