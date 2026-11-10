<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ForceCors
{
    public function handle(Request $request, Closure $next)
    {
        $allowedOrigin = 'https://airfly-b2b-portal-ij7d.vercel.app';
        $origin = $request->header('Origin');

        if ($origin !== $allowedOrigin) {
            return $next($request);
        }

        if ($request->isMethod('OPTIONS')) {
            $headers = $request->header('Access-Control-Request-Headers');

            return response('', 204)
                ->header('Access-Control-Allow-Origin', $allowedOrigin)
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS')
                ->header('Access-Control-Allow-Headers', $headers ?: 'Content-Type, Authorization, Accept, X-Requested-With')
                ->header('Access-Control-Max-Age', '86400')
                ->header('X-Airfly-CORS', 'ForceCors-Active')
                ->header('Vary', 'Origin');
        }

        $response = $next($request);

        $response->headers->set('Access-Control-Allow-Origin', $allowedOrigin);
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, Accept, X-Requested-With');
        $response->headers->set('X-Airfly-CORS', 'ForceCors-Active');
        $response->headers->set('Vary', 'Origin');

        return $response;
    }
}
