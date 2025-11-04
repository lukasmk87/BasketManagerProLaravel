<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Create early installation marker BEFORE Laravel boots to prevent database access
// This marker is checked by service providers, middleware, and config files
if (isset($_SERVER['REQUEST_URI']) && str_starts_with($_SERVER['REQUEST_URI'], '/install')) {
    $installingMarker = __DIR__ . '/../storage/installing';
    $installedMarker = __DIR__ . '/../storage/installed';

    // Only create installing marker if not already installed
    if (!file_exists($installedMarker) && !file_exists($installingMarker)) {
        @file_put_contents($installingMarker, date('Y-m-d H:i:s'));
    }
}

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
