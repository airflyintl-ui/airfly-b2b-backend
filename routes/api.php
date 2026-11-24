<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AdminAuthController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\AdminDashboardController;
use App\Http\Controllers\Api\AgentController;
use App\Http\Controllers\Api\FlightController;
use App\Http\Controllers\Api\LetsFgSandboxController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\RechargeController;
use App\Http\Controllers\Api\AirlineController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\AdminSettingsController;
use App\Http\Controllers\Api\AdminGroupFareController;

// API TEST
Route::get('/', function () {
    return response()->json([
        'success' => true,
        'message' => 'AirFly API Working Successfully',
    ]);
});

// AUTH
Route::post('/admin/login', [AdminAuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// PUBLIC AIRLINES
Route::get('/airlines', [AirlineController::class, 'index']);
Route::get('/airlines/{id}', [AirlineController::class, 'show']);

// PUBLIC FLIGHTS
Route::get('/flights', [FlightController::class, 'index']);
Route::get('/flights/{id}', [FlightController::class, 'show']);
Route::post('/search-flight', [FlightController::class, 'search']);

// PROTECTED AGENT ROUTES
Route::middleware(['auth:sanctum', 'agent.only'])->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/dashboard', [DashboardController::class, 'index']);
// LETSFG SANDBOX FLIGHT SEARCH - TEST ONLY
    Route::post('/sandbox/search-flight', [LetsFgSandboxController::class, 'search']);
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
    Route::middleware(['auth:admin', 'admin.only'])->group(function () {

    Route::get('/admin/dashboard', [AdminDashboardController::class, 'index']);
     // ADMIN GROUP FARE MANAGEMENT
    Route::get('/admin/group-fares', [AdminGroupFareController::class, 'index']);
    Route::post('/admin/group-fares', [AdminGroupFareController::class, 'store']);
    Route::get('/admin/group-fares/{id}', [AdminGroupFareController::class, 'show']);
    Route::put('/admin/group-fares/{id}', [AdminGroupFareController::class, 'update']);
    Route::delete('/admin/group-fares/{id}', [AdminGroupFareController::class, 'destroy']);
    // ADMIN SYSTEM SETTINGS
    Route::get('/admin/settings', [AdminSettingsController::class, 'index']);
    Route::put('/admin/settings', [AdminSettingsController::class, 'update']);
    // AIRLINE MANAGEMENT
    Route::post('/airlines', [AirlineController::class, 'store']);
    Route::put('/airlines/{id}', [AirlineController::class, 'update']);
    Route::delete('/airlines/{id}', [AirlineController::class, 'destroy']);

    // FLIGHT MANAGEMENT
    Route::post('/flights', [FlightController::class, 'store']);
    Route::put('/flights/{id}', [FlightController::class, 'update']);
    Route::delete('/flights/{id}', [FlightController::class, 'destroy']);

    // AGENT MANAGEMENT
    Route::get('/agents', [AgentController::class, 'index']);
    Route::get('/agents/{id}', [AgentController::class, 'show']);
    Route::put('/agents/{id}', [AgentController::class, 'update']);

    // AGENT PASSWORD RESET
    Route::post('/agents/{id}/reset-password', [AgentController::class, 'resetPassword']);

    // RECHARGE MANAGEMENT
    Route::get('/admin/recharges', [RechargeController::class, 'all']);
    Route::post('/recharges/{id}/approve', [RechargeController::class, 'approve']);
    Route::post('/recharges/{id}/reject', [RechargeController::class, 'reject']);

    // ADMIN WALLET MANAGEMENT
    Route::get('/admin/agents/wallets', [WalletController::class, 'adminIndex']);
    Route::get('/admin/agents/{id}/wallet-transactions', [WalletController::class, 'adminStatement']);
    Route::post('/admin/agents/{id}/wallet-adjustments', [WalletController::class, 'adminAdjust']);

   
    // ADMIN LOGOUT
    Route::post('/admin/logout', [AdminAuthController::class, 'logout']);

});
