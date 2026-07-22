<?php

declare(strict_types=1);

use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Behind Coolify's Traefik reverse proxy the app only ever sees the
        // internal Docker network, not a fixed proxy IP, so trust the
        // forwarding headers unconditionally (standard for containerized
        // deployments) — otherwise isSecure()/HSTS and generated URLs break.
        $middleware->trustProxies(at: '*');

        $middleware->validateCsrfTokens(except: [
            'webhooks/*',
        ]);
        $middleware->appendToGroup('web', SetLocale::class);
        $middleware->append(SecurityHeaders::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
