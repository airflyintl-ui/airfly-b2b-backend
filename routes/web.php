<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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
            'message' => 'Database Connection Failed'
        ], 500);
    }
});

Route::get('/admin-db-check', function () {
    try {
        $usersTable = Schema::hasTable('users');
        $tokensTable = Schema::hasTable('personal_access_tokens');

        $adminExists = false;

        if ($usersTable) {
            $adminExists = DB::table('users')
                ->where('email', 'admin@airflyintl.com')
                ->exists();
        }

        return response()->json([
            'success' => true,
            'users_table_exists' => $usersTable,
            'admin_exists' => $adminExists,
            'personal_access_tokens_table_exists' => $tokensTable,
            'message' => 'Admin database check completed.'
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Database check failed.'
        ], 500);
    }
});

Route::post('/run-sanctum-migration', function (\Illuminate\Http\Request $request) {
    $secret = config('admin.setup_key');

    if (!$secret || !hash_equals($secret, (string) $request->header('X-Migration-Key'))) {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized'
        ], 403);
    }

    try {
        \Illuminate\Support\Facades\Artisan::call('migrate', [
            '--path' => 'database/migrations/2019_12_14_000001_create_personal_access_tokens_table.php',
            '--force' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Sanctum migration executed.',
            'output' => \Illuminate\Support\Facades\Artisan::output()
        ]);

    } catch (\Throwable $e) {
        return response()->json([
            'success' => false,
            'message' => 'Migration failed.'
        ], 500);
    }
});
