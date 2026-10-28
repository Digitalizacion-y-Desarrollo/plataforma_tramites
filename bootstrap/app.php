<?php

use App\Http\Middleware\EnsureSolicitudAreaAccess;
use App\Http\Middleware\RequireAccesosIdentity;
use App\Http\Middleware\RequireAccesosRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'accesos.auth' => RequireAccesosIdentity::class,
            'accesos.role' => RequireAccesosRole::class,
            'accesos.solicitud-area' => EnsureSolicitudAreaAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
