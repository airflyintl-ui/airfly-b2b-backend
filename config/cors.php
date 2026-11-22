<?php

return [

    'paths' => [
        'api/*',
        'sanctum/csrf-cookie',
    ],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'https://airfly-b2b-portal.vercel.app',
        'https://airfly-b2b-portal-ij7d.vercel.app',
        'https://airfly-b2b-portal-git-main-airflyintl-uis-projects.vercel.app',

        // Previous Vercel deployment
        'https://airfly-b2b-portal-cylwt4wrm-airflyintl-uis-projects.vercel.app',
        'https://airfly-b2b-portal-ij7d-7rz8sns8i-airflyintl-uis-projects.vercel.app',
        // Current Vercel deployment
        'https://airfly-b2b-portal-ij7d-git-main-airflyintl-uis-projects.vercel.app',

        'http://localhost:3000',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
