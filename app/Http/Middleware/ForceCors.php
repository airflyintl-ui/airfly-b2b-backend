<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ForceCors
{
    public function handle(Request $request, Closure $next)
    {
        $origin = $request->header('Origin');

        $allowedOrigin = 'https://airfly-b2b-portal-ij7d.vercel.app';

        if (
            $request->is('api/*') &&
            $origin === $allowedOrigin
        ) {
            if ($request->isMethod('OPTIONS')) {
                return response('', 204)
                    ->header('Access-Control-Allow-Origin', $allowedOrigin)
                    ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS')
                    ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, Accept, X-Requested-With')
                    ->header('Access-Control-Max-Age', '86400')
                    ->header('Vary', 'Origin');
            }

            $response = $next($request);

            $response->headers->set('Access-Control-Allow-Origin', $allowedOrigin);
            $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, Accept, X-Requested-With');
            $response->headers->set('Vary', 'Origin');

            return $response;
        }

        return $next($request);
    }
}
