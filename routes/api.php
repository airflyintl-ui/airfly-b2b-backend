<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Http\Request;

use App\Http\Controllers\Api\AdminAuthController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\AdminDashboardController;
use App\Http\Controllers\Api\AgentController;
use App\Http\Controllers\Api\FlightController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\RechargeController;
use App\Http\Controllers\Api\AirlineController;
use App\Http\Controllers\Api\WalletController;

// API TEST
Route::get('/', function () {
    return response()->json([
        'success' => true,
        'message' => 'AirFly API Working Successfully',
    ]);
});

// TEMPORARY DATABASE MIGRATION
Route::post('/run-database-migrations', function (Request $request) {

    $secret = config('admin.setup_key');

    if (
        !$secret ||
        !hash_equals($secret, (string) $request->header('X-Migration-Key'))
    ) {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized',
        ], 403);
    }

    try {
        Artisan::call('migrate', [
            '--force' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Database migrations completed.',
            'output' => Artisan::output(),
        ]);

    } catch (\Throwable $e) {
        return response()->json([
            'success' => false,
            'message' => 'Migration failed. Check server logs.',
        ], 500);
    }
});

// AUTH
Route::post('/admin/login', [AdminAuthController::class, 'login']);

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// AIRLINES
Route::get('/airlines', [AirlineController::class, 'index']);
Route::post('/airlines', [AirlineController::class, 'store']);
Route::get('/airlines/{id}', [AirlineController::class, 'show']);
Route::put('/airlines/{id}', [AirlineController::class, 'update']);
Route::delete('/airlines/{id}', [AirlineController::class, 'destroy']);

// FLIGHTS
Route::get('/flights', [FlightController::class, 'index']);
Route::post('/flights', [FlightController::class, 'store']);
Route::get('/flights/{id}', [FlightController::class, 'show']);
Route::put('/flights/{id}', [FlightController::class, 'update']);
Route::delete('/flights/{id}', [FlightController::class, 'destroy']);
Route::post('/search-flight', [FlightController::class, 'search']);

// PROTECTED AGENT ROUTES
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/dashboard', [DashboardController::class, 'index']);

    Route::get('/wallet/statement', [WalletController::class, 'statement']);

    Route::get('/recharges', [RechargeController::class, 'index']);
    Route::post('/recharges', [RechargeController::class, 'store']);
    Route::get('/recharges/{id}', [RechargeController::class, 'show']);

    Route::get('/bookings', [BookingController::class, 'index']);
    Route::post('/bookings', [BookingController::class, 'store']);
    Route::get('/bookings/{id}', [BookingController::class, 'show']);
    Route::post('/bookings/{id}/cancel', [BookingController::class, 'cancel']);
    Route::get('/bookings/{id}/invoice', [BookingController::class, 'invoice']);
});

// PROTECTED ADMIN ROUTES
Route::middleware('auth:admin')->group(function () {

    Route::get('/admin/dashboard', [AdminDashboardController::class, 'index']);

    Route::get('/agents', [AgentController::class, 'index']);
    Route::get('/agents/{id}', [AgentController::class, 'show']);
    Route::put('/agents/{id}', [AgentController::class, 'update']);

    Route::get('/admin/recharges', [RechargeController::class, 'all']);
    Route::post('/recharges/{id}/approve', [RechargeController::class, 'approve']);
    Route::post('/recharges/{id}/reject', [RechargeController::class, 'reject']);

    Route::post('/admin/logout', [AdminAuthController::class, 'logout']);
});
