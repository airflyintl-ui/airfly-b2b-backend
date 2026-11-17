<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ForceCors
{
    public function handle(Request $request, Closure $next)
    {
        error_log('AIRFLY FORCE CORS MIDDLEWARE RUNNING');

        $allowedOrigin = 'https://airfly-b2b-portal-ij7d.vercel.app';

        if ($request->isMethod('OPTIONS') && $request->is('api/*')) {
            return response('', 204)
                ->header('Access-Control-Allow-Origin', $allowedOrigin)
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, Accept, X-Requested-With')
                ->header('Access-Control-Max-Age', '86400')
                ->header('X-Airfly-CORS', 'ForceCors-Diagnostic');
        }

        try {
    $response = $next($request);
} catch (\Throwable $e) {
    error_log('AIRFLY PIPELINE EXCEPTION: ' . get_class($e));
    error_log('AIRFLY ERROR MESSAGE: ' . $e->getMessage());
    error_log('AIRFLY ERROR FILE: ' . $e->getFile());
    error_log('AIRFLY ERROR LINE: ' . $e->getLine());

    throw $e;
}

        $response->headers->set('X-Airfly-CORS', 'ForceCors-Diagnostic');

        if ($request->is('api/*')) {
            $response->headers->set('Access-Control-Allow-Origin', $allowedOrigin);
            $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, Accept, X-Requested-With');
        }

        return $response;
    }
}
