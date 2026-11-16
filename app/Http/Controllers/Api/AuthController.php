<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Throwable;

class AuthController extends Controller
{
    /**
     * AGENT REGISTRATION
     * POST /api/register
     */
    public function register(Request $request)
    {
        error_log('AIRFLY REGISTER STEP 1: Controller reached');

        try {
            $validated = $request->validate([
                'agency_name' => 'required|string|max:255',
                'owner_name' => 'required|string|max:255',
                'email' => 'required|email|max:255|unique:agents,email',
                'phone' => 'required|string|max:30',
                'address' => 'required|string',
                'trade_license' => 'required|string|max:255|unique:agents,trade_license',
                'password' => 'required|string|min:6|confirmed',
            ]);

            error_log('AIRFLY REGISTER STEP 2: Validation passed');

            error_log('AIRFLY REGISTER STEP 3: Creating agent');

            $agent = Agent::create([
                'agency_name' => $validated['agency_name'],
                'owner_name' => $validated['owner_name'],
                'email' => $validated['email'],
                'mobile' => $validated['phone'],
                'address' => $validated['address'],
                'trade_license' => $validated['trade_license'],
                'password' => Hash::make($validated['password']),
                'wallet' => 0.00,
                'status' => 'pending',
            ]);

            error_log('AIRFLY REGISTER STEP 4: Agent created successfully');

            return response()->json([
                'success' => true,
                'message' => 'Agency Registration Submitted Successfully. Please wait for admin approval.',
                'agent' => $agent,
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {

            error_log('AIRFLY REGISTER VALIDATION FAILED: ' . json_encode($e->errors()));

            throw $e;

        } catch (Throwable $e) {

            error_log('AIRFLY REGISTER FAILED: ' . get_class($e));
            error_log('AIRFLY REGISTER ERROR: ' . $e->getMessage());
            error_log('AIRFLY REGISTER FILE: ' . $e->getFile());
            error_log('AIRFLY REGISTER LINE: ' . $e->getLine());

            Log::error('AIRFLY AGENT REGISTRATION FAILED', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Registration failed due to a server error. Please contact support.',
            ], 500);
        }
    }

    /**
     * AGENT LOGIN
     * POST /api/login
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $agent = Agent::where('email', $request->email)->first();

        if (!$agent || !Hash::check($request->password, $agent->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid email or password.',
            ], 401);
        }

        if ($agent->status !== 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Your agency account is pending admin approval.',
            ], 403);
        }

        $agent->tokens()->delete();

        $token = $agent->createToken('agent-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login Successful.',
            'token' => $token,
            'agent' => $agent,
        ]);
    }

    /**
     * AGENT LOGOUT
     * POST /api/logout
     */
    public function logout(Request $request)
    {
        $token = $request->user()->currentAccessToken();

        if ($token) {
            $token->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Logout Successful.',
        ]);
    }
}
