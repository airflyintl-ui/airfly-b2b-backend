<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/db-test', function () {
    try {
        DB::connection()->getPdo();

        return response()->json([
            'success' => true,
            'message' => 'Aiven MySQL Database Connected Successfully!'
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Database Connection Failed',
            'error' => $e->getMessage()
        ], 500);
    }
});
