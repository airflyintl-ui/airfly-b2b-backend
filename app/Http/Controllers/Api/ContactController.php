<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ContactController extends Controller
{
    public function send(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:150',
            'phone' => 'nullable|string|max:30',
            'message' => 'required|string|max:5000',
        ]);

        $apiKey = config('services.brevo.api_key');

        if (empty($apiKey)) {
            Log::error('Brevo API key is missing.');

            return response()->json([
                'success' => false,
                'message' => 'Email service is not configured.',
            ], 500);
        }

        try {
            $response = Http::withHeaders([
                'api-key' => $apiKey,
                'accept' => 'application/json',
                'content-type' => 'application/json',
            ])
            ->timeout(20)
            ->post(config('services.brevo.endpoint'), [
                'sender' => [
                    'name' => 'Airfly International',
                    'email' => 'airflyintl@gmail.com',
                ],
                'to' => [
                    [
                        'email' => 'airflyintl@gmail.com',
                        'name' => 'Airfly International',
                    ],
                ],
                'replyTo' => [
                    'email' => $validated['email'],
                    'name' => $validated['name'],
                ],
                'subject' => 'New Website Contact Message - Airfly International',
                'textContent' =>
                    "A new message has been received from the Airfly International website.\n\n" .
                    "Name: {$validated['name']}\n" .
                    "Email: {$validated['email']}\n" .
                    "Phone: " . ($validated['phone'] ?? 'Not provided') . "\n\n" .
                    "Message:\n{$validated['message']}",
            ]);

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Your message has been sent successfully.',
                ]);
            }

            Log::error('Brevo API email sending failed.', [
                'status' => $response->status(),
                'response' => $response->json(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to send your message right now. Please try again later.',
            ], 500);

        } catch (\Throwable $e) {
            Log::error('Brevo API connection failed.', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to send your message right now. Please try again later.',
            ], 500);
        }
    }
}
