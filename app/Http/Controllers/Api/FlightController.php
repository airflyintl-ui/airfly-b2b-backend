<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Flight;
use App\Services\LetsFGService;
use Illuminate\Support\Facades\Log;
use Throwable;

class FlightController extends Controller
{
    /**
     * Flight List
     */
    public function index()
    {
        return response()->json([
            'success' => true,
            'flights' => Flight::with('airline')
                ->latest()
                ->get()
        ]);
    }

    /**
     * Add Flight
     */
    public function store(Request $request)
    {
        $request->validate([
            'airline_id' => 'required|exists:airlines,id',
            'flight_no' => 'required',
            'from' => 'required',
            'to' => 'required',
            'departure_time' => 'required',
            'arrival_time' => 'required',
            'economy_fare' => 'required|numeric',
            'business_fare' => 'required|numeric',
            'available_seats' => 'required|integer',
        ]);

        $flight = Flight::create([
            'airline_id' => $request->airline_id,
            'flight_no' => strtoupper($request->flight_no),
            'from' => strtoupper($request->from),
            'to' => strtoupper($request->to),
            'departure_time' => $request->departure_time,
            'arrival_time' => $request->arrival_time,
            'economy_fare' => $request->economy_fare,
            'business_fare' => $request->business_fare,
            'available_seats' => $request->available_seats,
            'status' => 1,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Flight Added Successfully',
            'flight' => $flight
        ]);
    }

    /**
     * Main Flight Search
     *
     * POST /api/search-flight
     *
     * Current supplier: LetsFG Sandbox
     */
    public function search(
        Request $request,
        LetsFGService $letsFG
    ) {
        $request->validate([
            'from' => 'required|string|size:3',
            'to' => 'required|string|size:3',

            'date' => 'nullable|date_format:Y-m-d',
            'departure_date' => 'nullable|date_format:Y-m-d',

            'return_date' => 'nullable|date_format:Y-m-d',

            'trip_type' => 'nullable|in:oneway,roundtrip',

            'adults' => 'nullable|integer|min:1|max:9',
            'children' => 'nullable|integer|min:0|max:9',
            'infants' => 'nullable|integer|min:0|max:9',

            'cabin_class' => 'nullable|in:M,W,C,F',
            'max_stopovers' => 'nullable|integer|min:0|max:4',
            'limit' => 'nullable|integer|min:1|max:200',
        ]);

        $departureDate = $request->departure_date
            ?? $request->date;

        if (!$departureDate) {
            return response()->json([
                'success' => false,
                'message' => 'Departure date is required.',
            ], 422);
        }

        $from = strtoupper(trim($request->from));
        $to = strtoupper(trim($request->to));

        if ($from === $to) {
            return response()->json([
                'success' => false,
                'message' => 'Origin and destination cannot be the same.',
            ], 422);
        }

        $tripType = $request->trip_type ?? 'oneway';

        if (
            $tripType === 'roundtrip'
            && !$request->return_date
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Return date is required for round trip.',
            ], 422);
        }

        if (
            $request->return_date
            && $request->return_date < $departureDate
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Return date cannot be before departure date.',
            ], 422);
        }

        $adults = (int) ($request->adults ?? 1);
        $children = (int) ($request->children ?? 0);
        $infants = (int) ($request->infants ?? 0);

        if ($infants > $adults) {
            return response()->json([
                'success' => false,
                'message' => 'Number of infants cannot exceed number of adults.',
            ], 422);
        }

        try {
            $result = $letsFG->searchFlights([
                'from' => $from,
                'to' => $to,
                'departure_date' => $departureDate,
                'return_date' => $request->return_date,
                'trip_type' => $tripType,
                'adults' => $adults,
                'children' => $children,
                'infants' => $infants,
                'cabin_class' => $request->cabin_class,
                'max_stopovers' => $request->max_stopovers,
                'limit' => $request->limit ?? 50,
            ]);

            return response()->json([
                'success' => true,
                'source' => $result['source'],
                'source_tier' => $result['source_tier'],
                'message' => 'Flight search completed successfully.',

                'search' => [
                    'from' => $from,
                    'to' => $to,
                    'departure_date' => $departureDate,
                    'return_date' => $request->return_date,
                    'trip_type' => $tripType,
                    'adults' => $adults,
                    'children' => $children,
                    'infants' => $infants,
                ],

                'pricing' => $result['pricing'],
                'data' => $result['data'],
                'warning' => $result['warning'],
            ]);

        } catch (Throwable $e) {

            Log::error('AirFly Flight Search Error', [
                'message' => $e->getMessage(),
                'from' => $from,
                'to' => $to,
                'departure_date' => $departureDate,
            ]);

            if (
                str_contains(
                    $e->getMessage(),
                    'Please configure exchange rate'
                )
            ) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return response()->json([
                'success' => false,
                'source' => 'LetsFG Sandbox',
                'message' => 'Unable to search flights. Please try again.',
                'error' => config('app.debug')
                    ? $e->getMessage()
                    : null,
            ], 500);
        }
    }

    /**
     * Single Flight
     */
    public function show($id)
    {
        return response()->json([
            'success' => true,
            'flight' => Flight::with('airline')
                ->findOrFail($id)
        ]);
    }

    /**
     * Update Flight
     */
    public function update(Request $request, $id)
    {
        $flight = Flight::findOrFail($id);

        $flight->update([
            'airline_id' => $request->airline_id,
            'flight_no' => $request->flight_no,
            'from' => strtoupper($request->from),
            'to' => strtoupper($request->to),
            'departure_time' => $request->departure_time,
            'arrival_time' => $request->arrival_time,
            'economy_fare' => $request->economy_fare,
            'business_fare' => $request->business_fare,
            'available_seats' => $request->available_seats,
            'status' => $request->status ?? 1,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Flight Updated Successfully',
            'flight' => $flight
        ]);
    }

    /**
     * Delete Flight
     */
    public function destroy($id)
    {
        $flight = Flight::findOrFail($id);

        $flight->delete();

        return response()->json([
            'success' => true,
            'message' => 'Flight Deleted Successfully'
        ]);
    }
}
