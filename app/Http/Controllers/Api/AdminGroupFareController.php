<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GroupFareOffer;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminGroupFareController extends Controller
{
    /**
     * Admin: List All Group Fare Offers
     */
    public function index()
    {
        return response()->json([
            'success' => true,
            'offers' => GroupFareOffer::latest()->get()
        ]);
    }

    /**
     * Admin: Add New Group Fare Offer
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'airline_name' => 'required|string|max:255',
            'origin' => 'required|string|max:10',
            'destination' => 'required|string|max:10',
            'departure_date' => 'required|date',
            'return_date' => 'nullable|date|after_or_equal:departure_date',
            'fare' => 'required|numeric|min:0',
            'currency' => 'required|string|max:10',
            'total_seats' => 'required|integer|min:0',
            'available_seats' => 'required|integer|min:0|lte:total_seats',
            'description' => 'nullable|string',
            'terms_conditions' => 'nullable|string',
            'status' => [
                'sometimes',
                Rule::in(['draft', 'published', 'unpublished'])
            ],
        ]);

        $validated['airline_name'] = trim($validated['airline_name']);
        $validated['origin'] = strtoupper($validated['origin']);
        $validated['destination'] = strtoupper($validated['destination']);
        $validated['status'] = $validated['status'] ?? 'draft';

        $offer = GroupFareOffer::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Group Fare Offer Added Successfully',
            'offer' => $offer
        ], 201);
    }

    /**
     * Admin: View Single Offer
     */
    public function show($id)
    {
        $offer = GroupFareOffer::findOrFail($id);

        return response()->json([
            'success' => true,
            'offer' => $offer
        ]);
    }

    /**
     * Admin: Update Group Fare Offer
     */
    public function update(Request $request, $id)
    {
        $offer = GroupFareOffer::findOrFail($id);

        $validated = $request->validate([
            'airline_name' => 'sometimes|required|string|max:255',
            'origin' => 'sometimes|required|string|max:10',
            'destination' => 'sometimes|required|string|max:10',
            'departure_date' => 'sometimes|required|date',
            'return_date' => 'nullable|date',
            'fare' => 'sometimes|required|numeric|min:0',
            'currency' => 'sometimes|required|string|max:10',
            'total_seats' => 'sometimes|required|integer|min:0',
            'available_seats' => 'sometimes|required|integer|min:0',
            'description' => 'nullable|string',
            'terms_conditions' => 'nullable|string',
            'status' => [
                'sometimes',
                Rule::in(['draft', 'published', 'unpublished'])
            ],
        ]);

        if (isset($validated['airline_name'])) {
            $validated['airline_name'] = trim($validated['airline_name']);
        }

        if (isset($validated['origin'])) {
            $validated['origin'] = strtoupper($validated['origin']);
        }

        if (isset($validated['destination'])) {
            $validated['destination'] = strtoupper($validated['destination']);
        }

        $totalSeats = $validated['total_seats'] ?? $offer->total_seats;
        $availableSeats = $validated['available_seats'] ?? $offer->available_seats;

        if ($availableSeats > $totalSeats) {
            return response()->json([
                'success' => false,
                'message' => 'Available seats cannot exceed total seats.'
            ], 422);
        }

        $departureDate = $validated['departure_date'] ?? $offer->departure_date;
        $returnDate = $validated['return_date'] ?? $offer->return_date;

        if ($returnDate && $returnDate < $departureDate) {
            return response()->json([
                'success' => false,
                'message' => 'Return date cannot be before departure date.'
            ], 422);
        }

        $offer->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Group Fare Offer Updated Successfully',
            'offer' => $offer->fresh()
        ]);
    }

    /**
     * Admin: Delete Group Fare Offer
     */
    public function destroy($id)
    {
        $offer = GroupFareOffer::findOrFail($id);

        if ($offer->requests()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'This offer has requests and cannot be deleted.'
            ], 422);
        }

        $offer->delete();

        return response()->json([
            'success' => true,
            'message' => 'Group Fare Offer Deleted Successfully'
        ]);
    }
}
