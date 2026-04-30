<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Requests:  camelCase keys → snake_case (mobile sends camelCase, Laravel expects snake_case)
        // Responses: snake_case keys → camelCase (Laravel returns snake_case, mobile expects camelCase)
        $middleware->appendToGroup('api', \App\Http\Middleware\ConvertCamelToSnakeCase::class);
        $middleware->appendToGroup('api', \App\Http\Middleware\ConvertResponseKeysToCamelCase::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
