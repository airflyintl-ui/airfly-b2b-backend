<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Kernel::class);

$request = Request::capture();

error_log('AIRFLY REQUEST: ' . $request->method() . ' ' . $request->path());

$response = $kernel->handle($request);

error_log('AIRFLY RESPONSE STATUS: ' . $response->getStatusCode());

error_log('AIRFLY RESPONSE HEADERS: ' . json_encode($response->headers->all()));

$response->send();
error_log('AIRFLY PHP HEADERS SENT: ' . json_encode(headers_list()));

error_log('AIRFLY HEADERS SENT STATUS: ' . (headers_sent() ? 'YES' : 'NO'));
$kernel->terminate($request, $response);
