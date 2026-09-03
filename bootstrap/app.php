<?php

use App\Http\Middleware\ApplyLocalization;
use App\Http\Middleware\AuditAdminActivity;
use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsurePermission;
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
        $middleware->web(append: [ApplyLocalization::class]);
        $middleware->alias([
            'admin' => EnsureAdmin::class,
            'localization' => ApplyLocalization::class,
            'audit.admin' => AuditAdminActivity::class,
            'permission' => EnsurePermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
