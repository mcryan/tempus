<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\TimeEntryController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;

// Guest routes
Route::middleware(['web', 'guest'])->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
});

// Debug route
Route::middleware(['web'])->get('/debug-middleware', function (Request $request) {
    $kernel = app()->make(\Illuminate\Contracts\Http\Kernel::class);
    $reflection = new \ReflectionClass($kernel);
    $middlewareGroupsProperty = $reflection->getProperty('middlewareGroups');
    $middlewareGroupsProperty->setAccessible(true);
    $middlewareGroups = $middlewareGroupsProperty->getValue($kernel);
    
    $output = date('Y-m-d H:i:s') . " - Debug route hit\n";
    $output .= "Route middleware: " . implode(", ", $request->route()->middleware()) . "\n";
    $output .= "Web middleware: " . implode(", ", $middlewareGroups['web']) . "\n";
    $output .= "User authenticated: " . (Auth::check() ? 'Yes' : 'No') . "\n";
    $output .= "Session ID: " . $request->session()->getId() . "\n";
    $output .= "Inertia middleware exists: " . (class_exists(\App\Http\Middleware\HandleInertiaRequests::class) ? 'Yes' : 'No') . "\n";
    
    file_put_contents(
        storage_path('logs/middleware.log'),
        $output,
        FILE_APPEND
    );
    
    return 'Check logs';
});

// Protected routes
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/', function () {
        return redirect('/timer');
    });

    Route::get('/timer', function () {
        // Try direct filesystem write
        $logPath = storage_path('logs/debug.log');
        file_put_contents($logPath, date('Y-m-d H:i:s') . " - Timer route accessed\n", FILE_APPEND);
        
        // Try Laravel logging
        Log::info('Timer route hit', [
            'user' => Auth::user(),
            'middleware' => request()->route()->middleware(),
            'session_id' => request()->session()->getId()
        ]);

        // Try direct storage
        Storage::disk('local')->append('debug.log', date('Y-m-d H:i:s') . " - Timer route storage write test");

        return Inertia::render('TimeTracker');
    });

    Route::get('/projects', function () {
        return Inertia::render('Projects');
    });

    Route::get('/tags', function () {
        return Inertia::render('Tags');
    });

    // User-Client management routes (accessible to both users and admins)
    Route::get('api/users/{id}/clients', [UserController::class, 'getClients']);
    Route::put('api/users/{id}/clients', [UserController::class, 'updateClients']);

    // Admin-only routes
    Route::middleware(\App\Http\Middleware\AdminMiddleware::class)->group(function () {
        Route::get('/clients', function () {
            return Inertia::render('Clients');
        });

        Route::get('/users', function () {
            return Inertia::render('Users');
        });
    });

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');

    // API routes
    Route::prefix('api')->group(function () {
        Route::get('/user', function () {
            return Auth::user();
        });

        Route::get('/time-entries/current', [TimeEntryController::class, 'current']);
        Route::post('/time-entries/{timeEntry}/stop', [TimeEntryController::class, 'stop']);
        Route::apiResource('time-entries', TimeEntryController::class);
        Route::apiResource('projects', ProjectController::class);
        
        // Admin-only API routes
        Route::middleware(\App\Http\Middleware\AdminMiddleware::class)->group(function () {
            // Only require admin for non-GET operations on clients
            Route::post('clients', [ClientController::class, 'store']);
            Route::put('clients/{client}', [ClientController::class, 'update']);
            Route::delete('clients/{client}', [ClientController::class, 'destroy']);
            
            // Users resource remains fully admin-only
            Route::apiResource('users', UserController::class);
        });
        
        // Allow all authenticated users to view clients
        Route::get('clients', [ClientController::class, 'index']);
        Route::get('clients/{client}', [ClientController::class, 'show']);
        
        Route::apiResource('tags', TagController::class);
    });
});

// Redirect all other routes to timer
Route::middleware(['web'])->get('{any}', function () {
    return redirect('/timer');
})->where('any', '.*');
