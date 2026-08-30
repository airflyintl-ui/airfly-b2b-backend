<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Recharge;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // ==================================================
        // LOGGED-IN AGENT
        // ==================================================

        $agent = $request->user();

        if (!$agent) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        // ==================================================
        // TOTAL BOOKINGS FOR THIS AGENT
        // ==================================================

        $totalBookings = Booking::where(
            'agent_id',
            $agent->id
        )->count();

        // ==================================================
        // PENDING RECHARGES FOR THIS AGENT
        // ==================================================

        $pendingRecharges = Recharge::where(
            'agent_id',
            $agent->id
        )
        ->whereRaw('LOWER(status) = ?', ['pending'])
        ->count();

        // ==================================================
        // APPROVED RECHARGES FOR THIS AGENT
        // ==================================================

        $approvedRecharges = Recharge::where(
            'agent_id',
            $agent->id
        )
        ->whereRaw('LOWER(status) = ?', ['approved'])
        ->count();

        // ==================================================
        // LATEST BOOKINGS
        // ==================================================

        $latestBookings = Booking::where(
            'agent_id',
            $agent->id
        )
        ->latest()
        ->take(5)
        ->get();

        // ==================================================
        // LATEST RECHARGES
        // ==================================================

        $latestRecharges = Recharge::where(
            'agent_id',
            $agent->id
        )
        ->latest()
        ->take(5)
        ->get();

        // ==================================================
        // DASHBOARD RESPONSE
        // ==================================================

        return response()->json([
            'success' => true,

            'agent' => [
                'id' => $agent->id,
                'agency_name' => $agent->agency_name,
                'owner_name' => $agent->owner_name,
                'email' => $agent->email,
                'phone' => $agent->phone,
                'wallet' => $agent->wallet,
                'status' => $agent->status,
                'created_at' => $agent->created_at,
                'updated_at' => $agent->updated_at,
            ],

            'cards' => [
                'wallet_balance' => $agent->wallet,
                'total_bookings' => $totalBookings,
                'pending_recharges' => $pendingRecharges,
                'approved_recharges' => $approvedRecharges,
            ],

            'latest_bookings' => $latestBookings,

            'latest_recharges' => $latestRecharges,
        ]);
    }
}