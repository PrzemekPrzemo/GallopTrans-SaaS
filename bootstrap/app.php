<?php

use App\Http\Middleware\EnsureOrganization;
use App\Http\Middleware\EnsureSubscribed;
use App\Http\Middleware\EnsureSuperAdmin;
use App\Http\Middleware\SetLocale;
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
        // Dla każdego żądania webowego — najpierw locale (żeby tłumaczenia
        // były gotowe zanim wystartują widoki), potem tenant.id w kontenerze.
        $middleware->web(append: [SetLocale::class, SetTenantContext::class]);

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
