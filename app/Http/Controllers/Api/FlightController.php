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
     * Supports:
     * One Way
     * Round Trip
     * Multi-City
     */
    public function search(
        Request $request,
        LetsFGService $letsFG
    ) {
        $request->validate([
            'trip_type' => 'nullable|in:oneway,roundtrip,multicity',

            'from' => 'nullable|required_unless:trip_type,multicity|string|size:3',
            'to' => 'nullable|required_unless:trip_type,multicity|string|size:3',

            'date' => 'nullable|date_format:Y-m-d',
            'departure_date' => 'nullable|date_format:Y-m-d',
            'return_date' => 'nullable|date_format:Y-m-d',

            'segments' => 'required_if:trip_type,multicity|array|min:2|max:6',
            'segments.*.from' => 'required_if:trip_type,multicity|string|size:3',
            'segments.*.to' => 'required_if:trip_type,multicity|string|size:3',
            'segments.*.departure_date' => 'required_if:trip_type,multicity|date_format:Y-m-d',

            'adults' => 'nullable|integer|min:1|max:9',
            'children' => 'nullable|integer|min:0|max:9',
            'infants' => 'nullable|integer|min:0|max:9',

            'cabin_class' => 'nullable|in:M,W,C,F',
            'max_stopovers' => 'nullable|integer|min:0|max:4',
            'limit' => 'nullable|integer|min:1|max:200',
        ]);

        $tripType = $request->input('trip_type', 'oneway');

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

            /*
             * MULTI-CITY SEARCH
             */
            if ($tripType === 'multicity') {

                $segments = $request->input('segments', []);

                $previousSegment = null;

                foreach ($segments as $index => $segment) {

                    $from = strtoupper(trim($segment['from']));
                    $to = strtoupper(trim($segment['to']));
                    $date = $segment['departure_date'];

                    if ($from === $to) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Origin and destination cannot be the same.',
                            'segment' => $index + 1,
                        ], 422);
                    }

                    if ($previousSegment !== null) {

                        if ($from !== $previousSegment['to']) {
                            return response()->json([
                                'success' => false,
                                'message' => 'Multi-City segments must connect in sequence.',
                                'segment' => $index + 1,
                            ], 422);
                        }

                        if ($date < $previousSegment['departure_date']) {
                            return response()->json([
                                'success' => false,
                                'message' => 'Segment dates must be in chronological order.',
                                'segment' => $index + 1,
                            ], 422);
                        }
                    }

                    $segments[$index]['from'] = $from;
                    $segments[$index]['to'] = $to;

                    $previousSegment = [
                        'to' => $to,
                        'departure_date' => $date,
                    ];
                }

                $segmentResults = [];
                $pricing = null;

                /*
                 * Search each segment separately.
                 */
                foreach ($segments as $index => $segment) {

                    $result = $letsFG->searchFlights([
                        'from' => $segment['from'],
                        'to' => $segment['to'],
                        'departure_date' => $segment['departure_date'],

                        'adults' => $adults,
                        'children' => $children,
                        'infants' => $infants,

                        'cabin_class' => $request->cabin_class,
                        'max_stopovers' => $request->max_stopovers,
                        'limit' => $request->limit ?? 50,
                    ]);

                    $segmentData = $result['data'] ?? [];

                    $pricing = $result['pricing'] ?? $pricing;

                    $segmentResults[] = [
                        'segment_number' => $index + 1,
                        'from' => $segment['from'],
                        'to' => $segment['to'],
                        'departure_date' => $segment['departure_date'],

                        'total_results' => $segmentData['total_results']
                            ?? count($segmentData['offers'] ?? []),

                        'offers' => $segmentData['offers'] ?? [],
                    ];
                }

                return response()->json([
                    'success' => true,
                    'source' => 'LetsFG Sandbox - Test Only',
                    'source_tier' => 'sandbox',
                    'message' => 'Multi-City flight search completed successfully.',

                    'search' => [
                        'trip_type' => 'multicity',
                        'segments' => $segments,
                        'adults' => $adults,
                        'children' => $children,
                        'infants' => $infants,
                    ],

                    'pricing' => $pricing,

                    'data' => [
                        'trip_type' => 'multicity',
                        'segments' => $segmentResults,
                    ],

                    'warning' => 'Sandbox results are for testing only. They are not real flight inventory or live bookable fares.',
                ]);
            }

            /*
             * ONE WAY / ROUND TRIP
             * Existing functionality preserved.
             */

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
                'trip_type' => $tripType,
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
