<?php

use App\Http\Middleware\EnsureUserCanAccessAdminArea;
use App\Http\Middleware\EnsureUserIsActive;
use App\Support\FusoDoUsuario;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin.access' => EnsureUserCanAccessAdminArea::class,
            'active.user' => EnsureUserIsActive::class,
        ]);

        // O fuso do navegador é escrito pelo JavaScript em texto puro; sem esta
        // exceção o middleware tentaria decifrá-lo e o descartaria. O valor não
        // é sigiloso e é validado contra a lista IANA antes de ser usado.
        $middleware->encryptCookies(except: [FusoDoUsuario::COOKIE]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
