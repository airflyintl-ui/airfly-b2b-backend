<?php

namespace App\Http\Middleware;

use App\Models\Agent;
use Closure;
use Illuminate\Http\Request;

class EnsureAgent
{
    public function handle(Request $request, Closure $next)
    {
        if (!($request->user() instanceof Agent)) {
            return response()->json([
                'success' => false,
                'message' => 'Agent access only.',
            ], 403);
        }

        return $next($request);
    }
}
