<?php

use App\Http\Middleware\EnsureShiftIsOpen;
use App\Http\Middleware\EnsureUserHasPermission;
use App\Http\Middleware\EnsureUserIsOwner;
use App\Http\Middleware\HandleInertiaRequests;
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
        $middleware->web(append: [
            HandleInertiaRequests::class,
        ]);

        $middleware->redirectGuestsTo(function (Request $request) {
            return $request->is('pos*', 'shifts/open', 'shifts/close', 'shifts/cash-movements', 'receipts*')
                ? route('cashier.login')
                : route('login');
        });
        $middleware->redirectUsersTo(function (Request $request) {
            return $request->user()?->canAccessAdmin()
                ? route('dashboard')
                : route('pos.index');
        });

        $middleware->alias([
            'owner' => EnsureUserIsOwner::class,
            'permission' => EnsureUserHasPermission::class,
            'shift.open' => EnsureShiftIsOpen::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
