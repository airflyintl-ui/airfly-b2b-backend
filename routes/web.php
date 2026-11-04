<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/db-test', function () {
    try {
        DB::connection()->getPdo();

        return response()->json([
            'status' => 'success',
            'message' => 'Aiven MySQL Database Connected Successfully!'
        ]);

    } catch (\Throwable $e) {

        Log::error('Aiven DB connection failed', [
            'error' => $e->getMessage()
        ]);

        return response()->json([
            'status' => 'error',
            'message' => 'Database Connection Failed'
        ], 500);
    }
});
