<?php

use Illuminate\Http\Request;
use Illuminate\Foundation\Application;

/**
 * Laravel - Inicio del Framework
 */

define('LARAVEL_START', microtime(true));

// Si la app está en modo mantenimiento...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Registrar el autoloader de Composer
require __DIR__.'/../vendor/autoload.php';

// Crear la aplicación
$app = require_once __DIR__.'/../bootstrap/app.php';

// Crear el kernel de HTTP
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Capturar y manejar la request
$response = $kernel->handle(
    $request = Request::capture()
);

// Enviar la respuesta
$response->send();

// Terminar la ejecución del kernel
$kernel->terminate($request, $response);
