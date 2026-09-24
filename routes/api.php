<?php

use App\Http\Controllers\Api\V1\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/init', [AuthController::class, 'init'])->middleware('throttle:30,1');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::post('/license', [AuthController::class, 'license'])->middleware('throttle:20,1');
    Route::post('/check', [AuthController::class, 'check'])->middleware('throttle:60,1');
});
