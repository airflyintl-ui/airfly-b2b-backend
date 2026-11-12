<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

$allowedOrigin = 'https://airfly-b2b-portal-ij7d.vercel.app';

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

$requestPath = parse_url(
    $_SERVER['REQUEST_URI'] ?? '/',
    PHP_URL_PATH
);

if (
    $origin === $allowedOrigin &&
    ($requestPath === '/api' || strpos($requestPath, '/api/') === 0)
) {
    header('Access-Control-Allow-Origin: ' . $allowedOrigin);
    header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, Accept, X-Requested-With');
    header('Access-Control-Max-Age: 86400');
    header('Access-Control-Expose-Headers: X-Airfly-CORS');
    header('X-Airfly-CORS: Public-Index-Active');
    header('Vary: Origin');

    if ($requestMethod === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}

define('LARAVEL_START', microtime(true));

/*
|--------------------------------------------------------------------------
| Check If The Application Is Under Maintenance
|--------------------------------------------------------------------------
|
| If the application is in maintenance / demo mode via the "down" command
| we will load this file so that any pre-rendered content can be shown
| instead of starting the framework, which could cause an exception.
|
*/

if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

/*
|--------------------------------------------------------------------------
| Register The Auto Loader
|--------------------------------------------------------------------------
|
| Composer provides a convenient, automatically generated class loader for
| this application. We just need to utilize it! We'll simply require it
| into the script here so we don't need to manually load our classes.
|
*/

require __DIR__.'/../vendor/autoload.php';

/*
|--------------------------------------------------------------------------
| Run The Application
|--------------------------------------------------------------------------
|
| Once we have the application, we can handle the incoming request using
| the application's HTTP kernel. Then, we will send the response back
| to this client's browser, allowing them to enjoy our application.
|
*/



$app = require_once __DIR__.'/../bootstrap/app.php';



try {
    $kernel = $app->make(Kernel::class);

    $request = Request::capture();

    $response = $kernel->handle($request);

    header('Content-Type: application/json');

    echo json_encode([
        'stage' => 'kernel_returned',
        'status' => $response->getStatusCode(),
        'content_type' => $response->headers->get('Content-Type'),
        'body_length' => strlen($response->getContent()),
    ]);

} catch (\Throwable $e) {

    error_log('AIRFLY KERNEL ERROR: ' . $e);

    http_response_code(500);
    header('Content-Type: application/json');

    echo json_encode([
        'stage' => 'kernel_exception',
        'error_type' => get_class($e),
        'message' => $e->getMessage(),
    ]);
}

exit;
