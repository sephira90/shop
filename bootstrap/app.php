<?php

declare(strict_types=1);

use App\Http\Middleware\ApiRequestTelemetryMiddleware;
use App\Http\Middleware\CorrelationIdMiddleware;
use App\Http\Middleware\EnsureActiveApiUser;
use App\Http\Middleware\EnsureIdempotencyKeyMiddleware;
use App\Http\Middleware\EnsureRoleMiddleware;
use App\Support\Api\ApiExceptionRenderer;
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
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(CorrelationIdMiddleware::class);
        $middleware->append(ApiRequestTelemetryMiddleware::class);

        $middleware->alias([
            'active.api.user' => EnsureActiveApiUser::class,
            'idempotency.key' => EnsureIdempotencyKeyMiddleware::class,
            'role' => EnsureRoleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(static function (\Throwable $exception, \Illuminate\Http\Request $request): ?\Symfony\Component\HttpFoundation\Response {
            return app(ApiExceptionRenderer::class)($exception, $request);
        });
    })->create();
