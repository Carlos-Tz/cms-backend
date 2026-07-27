<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            // Two API surfaces: private admin API (JWT) and public read-only API.
            Route::middleware('api')->prefix('api/admin')->group(base_path('routes/admin.php'));
            Route::middleware('api')->prefix('api/public')->group(base_path('routes/public.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // API-only app: there is no `login` route to redirect guests to, so an
        // unauthenticated request must always end as a JSON 401.
        $middleware->redirectGuestsTo(fn () => null);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'status' => 'error',
                    'data' => null,
                    'meta' => ['message' => 'No autenticado'],
                ], 401);
            }
        });

        // Keep API 404s in Spanish instead of leaking the framework's English default.
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'status' => 'error',
                    'data' => null,
                    'meta' => ['message' => 'Recurso no encontrado'],
                ], 404);
            }
        });
    })->create();
