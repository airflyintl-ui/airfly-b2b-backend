
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminAuthController extends Controller
{
    /**
     * ADMIN LOGIN
     */
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $admin = User::where(
            'email',
            $validated['email']
        )->first();

        if (
            !$admin ||
            !Hash::check(
                $validated['password'],
                $admin->password
            )
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid admin email or password.',
            ], 401);
        }

        $token = $admin
            ->createToken('admin-token')
            ->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Admin Login Successful.',
            'token' => $token,
            'admin' => [
                'id' => $admin->id,
                'name' => $admin->name,
                'email' => $admin->email,
            ],
        ]);
    }

    /**
     * ADMIN LOGOUT
     */
    public function logout(Request $request)
    {
        $admin = $request->user();

        if ($admin) {
            $admin->currentAccessToken()->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Admin Logout Successful.',
        ]);
    }
}
