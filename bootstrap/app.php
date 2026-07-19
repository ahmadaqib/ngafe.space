<?php

use App\Exceptions\DomainException;
use App\Http\Middleware\AssignRequestId;
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
        $middleware->append(AssignRequestId::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (DomainException $exception, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->userMessage()], 422);
            }

            return back()->with('toast_error', $exception->userMessage());
        });
        $exceptions->render(function (Throwable $exception, Request $request) {
            if ($exception instanceof DomainException || ! $request->expectsJson()) {
                return null;
            }

            return response()->json(['message' => 'Ada yang error di kami, bukan di kamu…'], 500);
        });
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
