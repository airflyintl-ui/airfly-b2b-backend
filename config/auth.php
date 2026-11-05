
<?php

return [

    'defaults' => [
        'guard' => 'web',
        'passwords' => 'agents',
    ],

    'guards' => [

        'web' => [
            'driver' => 'session',
            'provider' => 'agents',
        ],

        // Agent API Authentication
        'sanctum' => [
            'driver' => 'sanctum',
            'provider' => 'agents',
        ],

        // Admin API Authentication
        'admin' => [
            'driver' => 'sanctum',
            'provider' => 'users',
        ],

    ],

    'providers' => [

        'agents' => [
            'driver' => 'eloquent',
            'model' => App\Models\Agent::class,
        ],

        'users' => [
            'driver' => 'eloquent',
            'model' => App\Models\User::class,
        ],

    ],

    'passwords' => [

        'agents' => [
            'provider' => 'agents',
            'table' => 'password_resets',
            'expire' => 60,
            'throttle' => 60,
        ],

    ],

    'password_timeout' => 10800,

];
