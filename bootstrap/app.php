<?php

declare(strict_types=1);

use App\Http\Middleware\ApiRequestTelemetryMiddleware;
use App\Http\Middleware\CorrelationIdMiddleware;
use App\Http\Middleware\EnsureRoleMiddleware;
use App\Support\Api\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

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
            'role' => EnsureRoleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(static function (\Throwable $exception, Request $request): ?Response {
            if (! $request->is('api/*')) {
                return null;
            }

            $status = match (true) {
                $exception instanceof ValidationException => 422,
                $exception instanceof AuthenticationException => 401,
                $exception instanceof AuthorizationException => 403,
                $exception instanceof HttpExceptionInterface => $exception->getStatusCode(),
                default => 500,
            };

            $message = $status >= 500 ? 'Internal server error.' : trim($exception->getMessage());
            if ($message === '') {
                $message = Response::$statusTexts[$status] ?? 'Request failed.';
            }

            $error = [
                'type' => class_basename($exception),
            ];

            if ($exception instanceof ValidationException) {
                $error['validation'] = $exception->errors();
            }

            return ApiResponse::error($message, $status, $error);
        });
    })->create();
