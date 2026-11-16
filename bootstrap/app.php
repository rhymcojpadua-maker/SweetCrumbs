<?php

use App\Http\Middleware\EnsureAccountActive;
use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\EnsureShopOperational;
use App\Http\Middleware\EnsureShopSubscription;
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
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'role' => EnsureRole::class,
            'account.active' => EnsureAccountActive::class,
            'shop.operational' => EnsureShopOperational::class,
            'shop.subscription' => EnsureShopSubscription::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'webhooks/xendit',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
