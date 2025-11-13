<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Excluir todas las rutas API del middleware CSRF
        $middleware->validateCsrfTokens(except: [
            'api/*',
        ]);
        
        // Habilitar CORS para todas las rutas API
        $middleware->api(prepend: [
            \Illuminate\Http\Middleware\HandleCors::class,
        ]);

        // Registrar alias de middleware personalizados
        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    // ✅ AÑADE ESTA LÍNEA para registrar comandos
    ->withCommands([
        __DIR__ . '/../app/Console/Commands',
    ])
    ->create();