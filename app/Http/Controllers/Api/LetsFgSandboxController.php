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
    private string $sandboxUrl =
        'https://letsfg.co/developers/api/v1/sandbox/flights/search';

    public function search(Request $request)
    {
        $tripType = $request->input('trip_type', 'oneway');

        $input = $request->all();

        $rules = [
            'trip_type' => ['required', 'in:oneway,roundtrip,multicity'],
            'adults' => ['nullable', 'integer', 'min:1', 'max:9'],
            'children' => ['nullable', 'integer', 'min:0', 'max:9'],
            'infants' => ['nullable', 'integer', 'min:0', 'max:9'],
            'cabin_class' => ['nullable', 'in:M,W,C,F'],
            'max_stopovers' => ['nullable', 'integer', 'min:0', 'max:4'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:200'],
        ];

        if ($tripType === 'multicity') {
            $rules['segments'] = ['required', 'array', 'min:2', 'max:6'];
            $rules['segments.*.from'] = [
                'required', 'string', 'regex:/^[A-Za-z]{3}$/'
            ];
            $rules['segments.*.to'] = [
                'required', 'string', 'regex:/^[A-Za-z]{3}$/'
            ];
            $rules['segments.*.departure_date'] = [
                'required', 'date_format:Y-m-d'
            ];
        } else {
            $rules['from'] = [
                'required', 'string', 'regex:/^[A-Za-z]{3}$/'
            ];
            $rules['to'] = [
                'required', 'string', 'regex:/^[A-Za-z]{3}$/'
            ];
            $rules['departure_date'] = [
                'required', 'date_format:Y-m-d'
            ];
            $rules['return_date'] = [
                'nullable',
                'date_format:Y-m-d',
                'after_or_equal:departure_date'
            ];
        }

        $validator = Validator::make($input, $rules);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid flight search information.',
                'errors' => $validator->errors(),
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

        $adults = (int) ($input['adults'] ?? 1);
        $children = (int) ($input['children'] ?? 0);
        $infants = (int) ($input['infants'] ?? 0);

        if ($infants > $adults) {
            return response()->json([
                'success' => false,
                'message' => 'Infants cannot exceed adults.',
            ], 422);
        }

        try {
            /*
             * MULTI-CITY SEARCH
             * Each segment is searched separately.
             */

            if ($tripType === 'multicity') {
                $segments = $input['segments'];

                for ($i = 0; $i < count($segments); $i++) {
                    $from = strtoupper($segments[$i]['from']);
                    $to = strtoupper($segments[$i]['to']);
                    $date = $segments[$i]['departure_date'];

                    if ($from === $to) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Origin and destination cannot be the same in Flight ' . ($i + 1) . '.',
                        ], 422);
                    }

                    if ($i > 0) {
                        $previous = $segments[$i - 1];

                        if ($from !== strtoupper($previous['to'])) {
                            return response()->json([
                                'success' => false,
                                'message' => 'Flight ' . ($i + 1) . ' must start from the previous flight destination.',
                            ], 422);
                        }

                        if ($date < $previous['departure_date']) {
                            return response()->json([
                                'success' => false,
                                'message' => 'Multi-City flight dates must be in chronological order.',
                            ], 422);
                        }
                    }
                }

                $multiCityResults = [];

                foreach ($segments as $index => $segment) {
                    $from = strtoupper($segment['from']);
                    $to = strtoupper($segment['to']);

                    $payload = [
                        'origin' => $from,
                        'destination' => $to,
                        'date_from' => $segment['departure_date'],
                        'adults' => $adults,
                        'children' => $children,
                        'infants' => $infants,
                        'currency' => 'USD',
                        'limit' => $input['limit'] ?? 50,
                        'sort' => 'price',
                    ];

                    if (!empty($input['cabin_class'])) {
                        $payload['cabin_class'] = $input['cabin_class'];
                    }

                    if (isset($input['max_stopovers'])) {
                        $payload['max_stopovers'] = $input['max_stopovers'];
                    }

                    $providerResult = $this->callSandbox($payload);

                    $offers = $this->addAirFlyPrices(
                        $providerResult['offers'] ?? [],
                        $exchangeRate,
                        $serviceCharge,
                        false
                    );

                    $multiCityResults[] = [
                        'segment_number' => $index + 1,
                        'from' => $from,
                        'to' => $to,
                        'departure_date' => $segment['departure_date'],
                        'offers' => $offers,
                    ];
                }

                return response()->json([
                    'success' => true,
                    'source' => 'LetsFG Sandbox - Test Only',
                    'source_tier' => 'sandbox',
                    'message' => 'Multi-City Sandbox search completed successfully.',
                    'search' => [
                        'trip_type' => 'multicity',
                        'segments' => $segments,
                        'adults' => $adults,
                        'children' => $children,
                        'infants' => $infants,
                    ],
                    'pricing' => [
                        'exchange_rate' => (float) $exchangeRate,
                        'service_charge' => (float) $serviceCharge,
                        'currency' => 'BDT',
                    ],
                    'data' => [
                        'segments' => $multiCityResults,
                    ],
                    'warning' => 'Sandbox results are simulated only. They cannot be booked.',
                ]);
            }

            /*
             * ONE WAY / ROUND TRIP SEARCH
             */

            $from = strtoupper($input['from']);
            $to = strtoupper($input['to']);

            if ($from === $to) {
                return response()->json([
                    'success' => false,
                    'message' => 'Origin and destination cannot be the same.',
                ], 422);
            }

            if (
                $tripType === 'roundtrip'
                && empty($input['return_date'])
            ) {
                return response()->json([
                    'success' => false,
                    'message' => 'Return date is required for round-trip search.',
                ], 422);
            }

            $payload = [
                'origin' => $from,
                'destination' => $to,
                'date_from' => $input['departure_date'],
                'adults' => $adults,
                'children' => $children,
                'infants' => $infants,
                'currency' => 'USD',
                'limit' => $input['limit'] ?? 50,
                'sort' => 'price',
            ];

            if ($tripType === 'roundtrip') {
                $payload['return_from'] = $input['return_date'];
            }

            if (!empty($input['cabin_class'])) {
                $payload['cabin_class'] = $input['cabin_class'];
            }

            if (isset($input['max_stopovers'])) {
                $payload['max_stopovers'] = $input['max_stopovers'];
            }

            $result = $this->callSandbox($payload);

            $result['offers'] = $this->addAirFlyPrices(
                $result['offers'] ?? [],
                $exchangeRate,
                $serviceCharge,
                true
            );

            return response()->json([
                'success' => true,
                'source' => 'LetsFG Sandbox - Test Only',
                'source_tier' => 'sandbox',
                'message' => 'Sandbox flight search completed successfully.',
                'search' => [
                    'trip_type' => $tripType,
                    'from' => $from,
                    'to' => $to,
                    'departure_date' => $input['departure_date'],
                    'return_date' => $input['return_date'] ?? null,
                    'adults' => $adults,
                    'children' => $children,
                    'infants' => $infants,
                ],
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
                'message' => 'Unable to complete LetsFG Sandbox search.',
                'error' => config('app.debug')
                    ? $e->getMessage()
                    : null,
            ], 502);
        }
    }

    /**
     * Send one search request to LetsFG Sandbox.
     */
    private function callSandbox(array $payload): array
    {
        $response = Http::acceptJson()
            ->asJson()
            ->timeout(30)
            ->post($this->sandboxUrl, $payload);

        if (!$response->successful()) {
            Log::error('LetsFG Sandbox API Error', [
                'status' => $response->status(),
                'response' => $response->body(),
                'payload' => $payload,
            ]);

            throw new \RuntimeException(
                'LetsFG Sandbox API returned HTTP ' . $response->status()
            );
        }

        $result = $response->json();

        if (!is_array($result)) {
            throw new \RuntimeException(
                'Invalid response received from LetsFG Sandbox.'
            );
        }

        return $result;
    }

    /**
     * Add AirFly BDT price to offers.
     */
    private function addAirFlyPrices(
        array $offers,
        $exchangeRate,
        $serviceCharge,
        bool $includeServiceCharge
    ): array {
        return collect($offers)
            ->map(function ($offer) use (
                $exchangeRate,
                $serviceCharge,
                $includeServiceCharge
            ) {
                $price = $offer['price'] ?? null;
                $currency = strtoupper($offer['currency'] ?? 'USD');

                $sellingPrice = null;

                if (is_numeric($price) && $currency === 'USD') {
                    $sellingPrice =
                        (float) $price * (float) $exchangeRate;

                    if ($includeServiceCharge) {
                        $sellingPrice += (float) $serviceCharge;
                    }

                    $sellingPrice = round($sellingPrice, 2);
                }

                $offer['airfly_price_bdt'] = $sellingPrice;

                return $offer;
            })
            ->values()
            ->all();
    }
}
