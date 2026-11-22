<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class LetsFGService
{
    public function searchFlights(array $data): array
    {
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
                ->post(
                    'https://letsfg.co/developers/api/v1/sandbox/flights/search',
                    $payload
                );

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
}
