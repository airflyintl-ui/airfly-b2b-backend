<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'agency_name' => 'required|string|max:255',
            'owner_name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:agents,email',
            'phone' => 'required|string|max:30',
            'address' => 'required|string',
            'trade_license' => 'required|string|max:255|unique:agents,trade_license',
            'password' => 'required|string|min:6|confirmed',
        ]);

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

        return response()->json([
            'success' => true,
            'message' => 'Agency Registration Submitted Successfully. Please wait for admin approval.',
            'agent' => $agent,
        ], 201);
    }

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
