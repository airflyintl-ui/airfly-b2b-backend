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
            'status' => 'success',
            'message' => 'Aiven MySQL Database Connected Successfully!'
        ]);

    } catch (\Throwable $e) {

        return response()->json([
            'status' => 'error',
            'diagnostic' => substr($e->getMessage(), 0, 500)
        ], 500);
    }
});
