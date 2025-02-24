<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TimeEntryController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ClientController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Basic test route
Route::get('/test', function() {
    return ['status' => 'ok'];
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // User info
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Project routes
    Route::apiResource('projects', ProjectController::class);
    
    // Time entry routes
    Route::get('time-entries/current', [TimeEntryController::class, 'current']);
    Route::post('time-entries/{timeEntry}/stop', [TimeEntryController::class, 'stop']);
    Route::apiResource('time-entries', TimeEntryController::class);
    
    // Tag routes
    Route::apiResource('tags', TagController::class);

    // Client routes accessible to all authenticated users
    Route::get('clients', [ClientController::class, 'index']);
    Route::get('clients/{client}', [ClientController::class, 'show']);

    // Admin-only routes
    Route::middleware(['admin'])->group(function () {
        // Users resource (admin only)
        Route::apiResource('users', UserController::class)->except('show');
        
        // Client management (admin only)
        Route::post('clients', [ClientController::class, 'store']);
        Route::put('clients/{client}', [ClientController::class, 'update']);
        Route::delete('clients/{client}', [ClientController::class, 'destroy']);
    });
});
