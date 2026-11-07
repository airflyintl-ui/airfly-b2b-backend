<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Http\Request;

Route::get('/', function () {
    return view('welcome');
});

// Temporary protected database migration route
Route::post('/run-database-migrations', function (Request $request) {

    $secret = config('admin.setup_key');

    if (
        !$secret ||
        !hash_equals($secret, (string) $request->header('X-Migration-Key'))
    ) {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized'
        ], 403);
    }

    try {

        Artisan::call('migrate', [
            '--force' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Database migrations completed.',
            'output' => Artisan::output()
        ]);

    } catch (\Throwable $e) {

        return response()->json([
            'success' => false,
            'message' => 'Migration failed. Check server logs.'
        ], 500);
    }
});
