<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class LetsFGService
{
    private string $searchUrl =
        'https://letsfg.co/developers/api/v1/sandbox/flights/search';

    public function searchFlights(array $data): array
    {
        if (!empty($data['segments']) && is_array($data['segments'])) {
            return $this->searchMultiCity($data);
        }

        // Existing One Way / Round Trip logic
        $exchangeRate = SystemSetting::getValue('exchange_rate');
        $serviceCharge = SystemSetting::getValue('service_charge');

        if ($exchangeRate === null || $serviceCharge === null) {
            throw new Exception(
                'Please configure exchange rate and service charge in Admin Settings.'
            );
        }

        $payload = [
            'origin' => strtoupper($data['from']),
            'destination' => strtoupper($data['to']),
            'date_from' => $data['departure_date'],
            'adults' => (int) ($data['adults'] ?? 1),
            'children' => (int) ($data['children'] ?? 0),
            'infants' => (int) ($data['infants'] ?? 0),
            'currency' => 'USD',
            'limit' => (int) ($data['limit'] ?? 50),
            'sort' => 'price',
        ];

        if (!empty($data['return_date'])) {
            $payload['return_from'] = $data['return_date'];
        }

        if (!empty($data['cabin_class'])) {
            $payload['cabin_class'] = $data['cabin_class'];
        }

        if (isset($data['max_stopovers'])) {
            $payload['max_stopovers'] = (int) $data['max_stopovers'];
        }

        try {
            $response = Http::acceptJson()
                ->asJson()
                ->timeout(30)
                ->post($this->searchUrl, $payload);

            if (!$response->successful()) {
                Log::error('LetsFG Sandbox API Error', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);

                throw new Exception(
                    'LetsFG API returned status ' . $response->status()
                );
            }

            $result = $response->json();

            $offers = collect($result['offers'] ?? [])
                ->map(function ($offer) use ($exchangeRate, $serviceCharge) {
                    $price = $offer['price'] ?? null;
                    $currency = strtoupper($offer['currency'] ?? 'USD');

                    $sellingPrice = null;

                    if (is_numeric($price) && $currency === 'USD') {
                        $sellingPrice = round(
                            ((float) $price * (float) $exchangeRate)
                            + (float) $serviceCharge,
                            2
                        );
                    }

                    $offer['airfly_price_bdt'] = $sellingPrice;

                    return $offer;
                })
                ->values()
                ->all();

            $result['offers'] = $offers;

            return [
                'success' => true,
                'source' => 'LetsFG Sandbox - Test Only',
                'source_tier' => 'sandbox',
                'pricing' => [
                    'exchange_rate' => (float) $exchangeRate,
                    'service_charge' => (float) $serviceCharge,
                    'currency' => 'BDT',
                ],
                'data' => $result,
                'warning' => 'Sandbox results are for testing only. They are not real flight inventory or live bookable fares.',
            ];

        } catch (\Throwable $e) {
            Log::error('LetsFG Flight Search Error', [
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Multi-City Search
     *
     * Each segment is searched separately.
     * Service charge is NOT added to individual segment fares.
     * It will be added once to the complete itinerary total.
     */
    private function searchMultiCity(array $data): array
    {
        $exchangeRate = SystemSetting::getValue('exchange_rate');
        $serviceCharge = SystemSetting::getValue('service_charge');

        if ($exchangeRate === null || $serviceCharge === null) {
            throw new Exception(
                'Please configure exchange rate and service charge in Admin Settings.'
            );
        }

        $segments = $data['segments'];

        if (count($segments) < 2 || count($segments) > 6) {
            throw new Exception(
                'Multi-City search requires between 2 and 6 segments.'
            );
        }

        $results = [];

        try {
            foreach ($segments as $index => $segment) {
                $origin = strtoupper(
                    $segment['from'] ?? $segment['origin'] ?? ''
                );

                $destination = strtoupper(
                    $segment['to'] ?? $segment['destination'] ?? ''
                );

                $departureDate =
                    $segment['departure_date']
                    ?? $segment['date_from']
                    ?? '';

                if (
                    empty($origin) ||
                    empty($destination) ||
                    empty($departureDate)
                ) {
                    throw new Exception(
                        'Origin, destination and departure date are required for segment '
                        . ($index + 1)
                    );
                }

                $payload = [
                    'origin' => $origin,
                    'destination' => $destination,
                    'date_from' => $departureDate,
                    'adults' => (int) ($data['adults'] ?? 1),
                    'children' => (int) ($data['children'] ?? 0),
                    'infants' => (int) ($data['infants'] ?? 0),
                    'currency' => 'USD',
                    'limit' => (int) ($data['limit'] ?? 50),
                    'sort' => 'price',
                ];

                if (!empty($data['cabin_class'])) {
                    $payload['cabin_class'] = $data['cabin_class'];
                }

                if (isset($data['max_stopovers'])) {
                    $payload['max_stopovers'] =
                        (int) $data['max_stopovers'];
                }

                $response = Http::acceptJson()
                    ->asJson()
                    ->timeout(30)
                    ->post($this->searchUrl, $payload);

                if (!$response->successful()) {
                    Log::error('LetsFG Multi-City Segment Error', [
                        'segment' => $index + 1,
                        'status' => $response->status(),
                        'response' => $response->body(),
                    ]);

                    throw new Exception(
                        'LetsFG search failed for segment '
                        . ($index + 1)
                        . ' (HTTP '
                        . $response->status()
                        . ').'
                    );
                }

                $result = $response->json();

                $offers = collect($result['offers'] ?? [])
                    ->map(function ($offer) use ($exchangeRate) {
                        $price = $offer['price'] ?? null;
                        $currency = strtoupper(
                            $offer['currency'] ?? 'USD'
                        );

                        $supplierPriceBdt = null;

                        if (
                            is_numeric($price) &&
                            $currency === 'USD'
                        ) {
                            $supplierPriceBdt = round(
                                (float) $price * (float) $exchangeRate,
                                2
                            );
                        }

                        // Service charge is added once to the
                        // complete Multi-City itinerary later.
                        $offer['supplier_price_bdt'] =
                            $supplierPriceBdt;

                        return $offer;
                    })
                    ->values()
                    ->all();

                $result['offers'] = $offers;

                $results[] = [
                    'segment_number' => $index + 1,
                    'origin' => $origin,
                    'destination' => $destination,
                    'departure_date' => $departureDate,
                    'success' => true,
                    'data' => $result,
                ];
            }

            return [
                'success' => true,
                'source' => 'LetsFG Sandbox - Test Only',
                'source_tier' => 'sandbox',
                'trip_type' => 'multi_city',
                'pricing' => [
                    'exchange_rate' => (float) $exchangeRate,
                    'service_charge' => (float) $serviceCharge,
                    'service_charge_applied_once' => true,
                    'currency' => 'BDT',
                    'note' => 'Select one offer from each segment. Add all selected supplier fares converted to BDT, then add the service charge once.',
                ],
                'segments' => $results,
                'warning' => 'Sandbox results are for testing only. They are not real flight inventory or live bookable fares.',
            ];

        } catch (\Throwable $e) {
            Log::error('LetsFG Multi-City Search Error', [
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
