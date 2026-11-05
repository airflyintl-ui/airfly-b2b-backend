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

Route::get('/admin-db-check', function () {
    try {
        $tableExists = \Illuminate\Support\Facades\Schema::hasTable('users');

        if (!$tableExists) {
            return response()->json([
                'success' => false,
                'users_table_exists' => false,
                'message' => 'Users table does not exist.'
            ]);
        }

        $adminExists = \Illuminate\Support\Facades\DB::table('users')
            ->where('email', 'admin@airflyintl.com')
            ->exists();

        return response()->json([
            'success' => true,
            'users_table_exists' => true,
            'admin_exists' => $adminExists,
            'message' => $adminExists
                ? 'Admin account found.'
                : 'Admin account not found.'
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Admin database check failed.'
        ], 500);
    }
});
