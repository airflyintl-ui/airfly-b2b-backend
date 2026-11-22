<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Throwable;

class LetsFgSandboxController extends Controller
{
    public function search(Request $request)
    {
        // Accept both existing AirFly field names and LetsFG field names.
        $origin = strtoupper(
            $request->input('from', $request->input('origin', ''))
        );

        $destination = strtoupper(
            $request->input('to', $request->input('destination', ''))
        );

        $departureDate = $request->input(
            'date',
            $request->input(
                'departure_date',
                $request->input('date_from')
            )
        );

        $returnDate = $request->input(
            'return_date',
            $request->input('return_from')
        );

        $input = array_merge($request->all(), [
            'origin' => $origin,
            'destination' => $destination,
            'date_from' => $departureDate,
            'return_from' => $returnDate,
        ]);

        $validator = Validator::make($input, [
            'origin' => ['required', 'string', 'size:3'],
            'destination' => ['required', 'string', 'size:3'],
            'date_from' => ['required', 'date_format:Y-m-d'],
            'return_from' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'trip_type' => ['nullable', 'in:oneway,roundtrip'],
            'adults' => ['nullable', 'integer', 'min:1', 'max:9'],
            'children' => ['nullable', 'integer', 'min:0', 'max:9'],
            'infants' => ['nullable', 'integer', 'min:0', 'max:9'],
            'cabin_class' => ['nullable', 'in:M,W,C,F'],
            'max_stopovers' => ['nullable', 'integer', 'min:0', 'max:4'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid flight search information.',
                'errors' => $validator->errors(),
            ], 422);
        }

        if (
            $input['trip_type'] === 'roundtrip'
            && empty($input['return_from'])
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Return date is required for round-trip search.',
            ], 422);
        }

        // Read AirFly pricing settings.
        $exchangeRate = SystemSetting::getValue('exchange_rate');
        $serviceCharge = SystemSetting::getValue('service_charge');

        if ($exchangeRate === null || $serviceCharge === null) {
            return response()->json([
                'success' => false,
                'message' => 'Please configure exchange rate and service charge in Admin Settings first.',
            ], 422);
        }

        // Prepare LetsFG Sandbox request.
        $payload = [
            'origin' => $input['origin'],
            'destination' => $input['destination'],
            'date_from' => $input['date_from'],
            'adults' => $input['adults'] ?? 1,
            'children' => $input['children'] ?? 0,
            'infants' => $input['infants'] ?? 0,
            'currency' => 'USD',
            'limit' => $input['limit'] ?? 50,
            'sort' => 'price',
        ];

        if (!empty($input['return_from'])) {
            $payload['return_from'] = $input['return_from'];
        }

        if (!empty($input['cabin_class'])) {
            $payload['cabin_class'] = $input['cabin_class'];
        }

        if (isset($input['max_stopovers'])) {
            $payload['max_stopovers'] = $input['max_stopovers'];
        }

        try {
            $response = Http::acceptJson()
                ->asJson()
                ->timeout(30)
                ->post(
                    'https://letsfg.co/developers/api/v1/sandbox/flights/search',
                    $payload
                );

            if (!$response->successful()) {
                Log::error('LetsFG Sandbox API Error', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'LetsFG Sandbox search failed.',
                    'provider_status' => $response->status(),
                    'provider_response' => $response->json() ?? $response->body(),
                ], 502);
            }

            $result = $response->json();

            // Calculate AirFly BDT selling price for each USD offer.
            $offers = collect($result['offers'] ?? [])
                ->map(function ($offer) use ($exchangeRate, $serviceCharge) {

                    $price = $offer['price'] ?? null;
                    $currency = strtoupper($offer['currency'] ?? 'USD');

                    $sellingPrice = null;

                    if (
                        is_numeric($price)
                        && $currency === 'USD'
                    ) {
                        $sellingPrice = round(
                            ((float) $price * (float) $exchangeRate)
                            + (float) $serviceCharge,
                            2
                        );
                    }

                    $offer['airfly_price_bdt'] = $sellingPrice;

                    return $offer;
                })
                ->values();

            $result['offers'] = $offers;

            return response()->json([
                'success' => true,
                'source' => 'LetsFG Sandbox - Test Only',
                'source_tier' => 'sandbox',
                'message' => 'Sandbox flight search completed successfully.',
                'pricing' => [
                    'exchange_rate' => (float) $exchangeRate,
                    'service_charge' => (float) $serviceCharge,
                    'currency' => 'BDT',
                ],
                'data' => $result,
                'warning' => 'Sandbox results are for testing only. They are not real flight inventory or live bookable fares.',
            ]);

        } catch (Throwable $e) {

            Log::error('LetsFG Sandbox Search Exception', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to connect to LetsFG Sandbox.',
            ], 500);
        }
    }
}
