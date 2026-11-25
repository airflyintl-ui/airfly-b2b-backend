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
use App\Http\Controllers\Api\AgentGroupFareController;
use App\Http\Controllers\Api\AdminGroupFareRequestController;
use App\Http\Controllers\Api\AdminHomepagePosterController;
use App\Http\Controllers\Api\AdminVisaInformationController;
use App\Http\Controllers\Api\AgentVisaRequestController;
use App\Http\Controllers\Api\AdminVisaRequestController;

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

// PUBLIC GROUP FARES
Route::get('/public/group-fares', [AgentGroupFareController::class, 'index']);

// PUBLIC HOMEPAGE POSTERS
Route::get('/public/homepage-posters', [
    AdminHomepagePosterController::class,
    'publicIndex'
]);

// PUBLIC CHINA VISA INFORMATION
Route::get('/public/visa-information', [
    AdminVisaInformationController::class,
    'publicIndex'
]);

// PROTECTED AGENT ROUTES
Route::middleware(['auth:sanctum', 'agent.only'])->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/dashboard', [DashboardController::class, 'index']);

    // LETSFG SANDBOX FLIGHT SEARCH - TEST ONLY
    Route::post('/sandbox/search-flight', [LetsFgSandboxController::class, 'search']);

    // WALLET
    Route::get('/wallet/statement', [WalletController::class, 'statement']);

    // RECHARGES
    Route::get('/recharges', [RechargeController::class, 'index']);
    Route::post('/recharges', [RechargeController::class, 'store']);
    Route::get('/recharges/{id}', [RechargeController::class, 'show']);

    // BOOKINGS
    Route::get('/bookings', [BookingController::class, 'index']);
    Route::post('/bookings', [BookingController::class, 'store']);
    Route::get('/bookings/{id}', [BookingController::class, 'show']);
    Route::post('/bookings/{id}/cancel', [BookingController::class, 'cancel']);
    Route::get('/bookings/{id}/invoice', [BookingController::class, 'invoice']);

    // AGENT GROUP FARE
    Route::get('/group-fares', [AgentGroupFareController::class, 'index']);
    Route::post('/group-fare-requests', [AgentGroupFareController::class, 'store']);
    Route::get('/group-fare-requests', [AgentGroupFareController::class, 'myRequests']);

    // AGENT VISA APPLICATIONS
    Route::post('/visa-requests', [
        AgentVisaRequestController::class,
        'store'
    ]);

    Route::get('/visa-requests', [
        AgentVisaRequestController::class,
        'index'
    ]);

    Route::get('/visa-requests/{id}', [
        AgentVisaRequestController::class,
        'show'
    ]);
});

// PROTECTED ADMIN ROUTES
Route::middleware(['auth:admin', 'admin.only'])->group(function () {

    // ADMIN DASHBOARD
    Route::get('/admin/dashboard', [AdminDashboardController::class, 'index']);

    // ADMIN GROUP FARE REQUEST MANAGEMENT
    Route::get('/admin/group-fare-requests', [AdminGroupFareRequestController::class, 'index']);
    Route::get('/admin/group-fare-requests/{id}', [AdminGroupFareRequestController::class, 'show']);
    Route::put('/admin/group-fare-requests/{id}', [AdminGroupFareRequestController::class, 'update']);

    // ADMIN GROUP FARE MANAGEMENT
    Route::get('/admin/group-fares', [AdminGroupFareController::class, 'index']);
    Route::post('/admin/group-fares', [AdminGroupFareController::class, 'store']);
    Route::get('/admin/group-fares/{id}', [AdminGroupFareController::class, 'show']);
    Route::put('/admin/group-fares/{id}', [AdminGroupFareController::class, 'update']);
    Route::delete('/admin/group-fares/{id}', [AdminGroupFareController::class, 'destroy']);

    // ADMIN HOMEPAGE POSTER MANAGEMENT
    Route::get('/admin/homepage-posters', [AdminHomepagePosterController::class, 'index']);
    Route::post('/admin/homepage-posters', [AdminHomepagePosterController::class, 'store']);
    Route::post('/admin/homepage-posters/{id}', [AdminHomepagePosterController::class, 'update']);
    Route::delete('/admin/homepage-posters/{id}', [AdminHomepagePosterController::class, 'destroy']);

    // ADMIN VISA INFORMATION MANAGEMENT
    Route::get('/admin/visa-information', [
        AdminVisaInformationController::class,
        'index'
    ]);

    Route::get('/admin/visa-information/{id}', [
        AdminVisaInformationController::class,
        'show'
    ]);

    Route::post('/admin/visa-information', [
        AdminVisaInformationController::class,
        'store'
    ]);

    Route::put('/admin/visa-information/{id}', [
        AdminVisaInformationController::class,
        'update'
    ]);

    Route::delete('/admin/visa-information/{id}', [
        AdminVisaInformationController::class,
        'destroy'
    ]);

    // ADMIN VISA REQUEST MANAGEMENT
    Route::get('/admin/visa-requests', [
        AdminVisaRequestController::class,
        'index'
    ]);

    Route::get('/admin/visa-requests/{id}', [
        AdminVisaRequestController::class,
        'show'
    ]);

    Route::put('/admin/visa-requests/{id}', [
        AdminVisaRequestController::class,
        'update'
    ]);

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
