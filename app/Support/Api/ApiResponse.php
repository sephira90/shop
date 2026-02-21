<?php

declare(strict_types=1);

namespace App\Support\Api;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

final class ApiResponse
{
    /**
     * Return standard data envelope.
     */
    public static function data(mixed $data, int $status = 200): JsonResponse
    {
        return response()->json([
            'data' => $data,
        ], $status);
    }

    /**
     * Return custom payload envelope.
     *
     * @param  array<string, mixed>  $payload
     */
    public static function payload(array $payload, int $status = 200): JsonResponse
    {
        return response()->json($payload, $status);
    }

    /**
     * Return list envelope with pagination meta.
     *
     * @param  LengthAwarePaginator<int, mixed>  $paginator
     */
    public static function paginated(mixed $items, LengthAwarePaginator $paginator): JsonResponse
    {
        return response()->json([
            'data' => $items,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /**
     * Return standard error envelope.
     *
     * @param  array<string, mixed>  $details
     */
    public static function error(string $message, int $status, array $details = []): JsonResponse
    {
        $error = ['message' => $message];
        $correlationId = request()->headers->get('X-Correlation-Id');

        if (is_string($correlationId) && $correlationId !== '') {
            $error['request_id'] = $correlationId;
        }

        if ($details !== []) {
            $error = [...$error, ...$details];
        }

        return response()->json([
            'error' => $error,
        ], $status);
    }

    /**
     * Return standard deletion payload.
     */
    public static function deleted(): JsonResponse
    {
        return response()->json([
            'data' => [
                'deleted' => true,
            ],
        ]);
    }
}
