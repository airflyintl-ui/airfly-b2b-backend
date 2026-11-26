<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

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

        try {
            Mail::raw(
                "A new message has been received from the Airfly International website.\n\n" .
                "Name: {$validated['name']}\n" .
                "Email: {$validated['email']}\n" .
                "Phone: " . ($validated['phone'] ?? 'Not provided') . "\n\n" .
                "Message:\n{$validated['message']}",
                function ($mail) use ($validated) {
                    $mail->to('airflyintl@gmail.com')
                        ->subject('New Website Contact Message - Airfly International')
                        ->replyTo(
                            $validated['email'],
                            $validated['name']
                        );
                }
            );

            return response()->json([
                'success' => true,
                'message' => 'Your message has been sent successfully.',
            ]);
        } catch (\Throwable $e) {
            Log::error('Airfly contact email failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to send your message right now. Please try again later.',
            ], 500);
        }
    }
}
