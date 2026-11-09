<?php

use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\CatalogController;
use App\Http\Middleware\AuthenticatePartner;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')
    ->middleware([AuthenticatePartner::class, 'throttle:partner-api'])
    ->group(function () {
        Route::get('hotels', [CatalogController::class, 'hotels']);
        Route::get('courses', [CatalogController::class, 'courses']);
        Route::get('courses/{course}/tee-times', [CatalogController::class, 'teeTimes']);

        Route::post('quotes', [BookingController::class, 'quote']);
        Route::post('bookings', [BookingController::class, 'store']);
        Route::get('bookings/{reference}', [BookingController::class, 'show']);
        Route::delete('bookings/{reference}', [BookingController::class, 'destroy']);
    });
