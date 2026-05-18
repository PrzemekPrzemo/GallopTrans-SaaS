<?php

use App\Http\Middleware\EnsureOrganization;
use App\Http\Middleware\EnsureSubscribed;
use App\Http\Middleware\EnsureSuperAdmin;
use App\Http\Middleware\SetTenantContext;
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
        // Dla każdego żądania webowego - wstrzyknij tenant.id do kontenera.
        $middleware->web(append: [SetTenantContext::class]);

        // Aliasy do użycia w routach.
        $middleware->alias([
            'ensure.org'         => EnsureOrganization::class,
            'ensure.subscribed'  => EnsureSubscribed::class,
            'ensure.super_admin' => EnsureSuperAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
