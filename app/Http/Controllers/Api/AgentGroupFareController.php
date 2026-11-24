<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GroupFareOffer;
use App\Models\GroupFareRequest;
use Illuminate\Http\Request;

class AgentGroupFareController extends Controller
{
    /**
     * Agent: View Published Group Fare Offers
     */
    public function index()
    {
        $offers = GroupFareOffer::where('status', 'published')
            ->where('available_seats', '>', 0)
            ->whereDate('departure_date', '>=', now()->toDateString())
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'offers' => $offers
        ]);
    }

    /**
     * Agent: Submit Group Fare Request
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'group_fare_offer_id' => 'required|integer|exists:group_fare_offers,id',
            'passenger_count' => 'required|integer|min:1',
            'contact_person' => 'required|string|max:255',
            'contact_number' => 'required|string|max:30',
            'contact_email' => 'nullable|email|max:255',
            'document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $offer = GroupFareOffer::where('id', $validated['group_fare_offer_id'])
            ->where('status', 'published')
            ->whereDate('departure_date', '>=', now()->toDateString())
            ->first();

        if (!$offer) {
            return response()->json([
                'success' => false,
                'message' => 'This Group Fare Offer is not available.'
            ], 404);
        }

        if ($validated['passenger_count'] > $offer->available_seats) {
            return response()->json([
                'success' => false,
                'message' => 'Requested passengers exceed available seats.'
            ], 422);
        }

        $documentPath = null;

        if ($request->hasFile('document')) {
            $documentPath = $request->file('document')
                ->store('group-fare-documents');
        }

        $groupFareRequest = GroupFareRequest::create([
            'group_fare_offer_id' => $offer->id,
            'agent_id' => $request->user()->id,
            'passenger_count' => $validated['passenger_count'],
            'contact_person' => $validated['contact_person'],
            'contact_number' => $validated['contact_number'],
            'contact_email' => $validated['contact_email'] ?? null,
            'document_path' => $documentPath,
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Group Fare Request Submitted Successfully',
            'request' => $groupFareRequest
        ], 201);
    }

    /**
     * Agent: View Own Group Fare Requests
     */
    public function myRequests(Request $request)
    {
        $requests = GroupFareRequest::with('offer')
            ->where('agent_id', $request->user()->id)
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'requests' => $requests
        ]);
    }
}
