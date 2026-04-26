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
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->statefulApi();
        $middleware->validateCsrfTokens(except: [
            'api/*',
        ]);
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'seller' => \App\Http\Middleware\SellerMiddleware::class,
            'customer' => \App\Http\Middleware\CustomerMiddleware::class,
            'support' => \App\Http\Middleware\SupportMiddleware::class,
            'admin_or_seller' => \App\Http\Middleware\AdminOrSellerMiddleware::class,
            'admin_or_support' => \App\Http\Middleware\AdminOrSupportMiddleware::class,
            'locale' => \App\Http\Middleware\LocaleMiddleware::class,
            'affiliate_tracking' => \App\Http\Middleware\AffiliateTrackingMiddleware::class,
            'rider' => \App\Http\Middleware\RiderMiddleware::class,
        ]);

        $middleware->appendToGroup('api', [
            \App\Http\Middleware\LocaleMiddleware::class,
            \App\Http\Middleware\AffiliateTrackingMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
