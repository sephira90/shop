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
     * Return list envelope with explicit pagination meta payload.
     *
     * @param  array{
     *     current_page:int,
     *     last_page:int,
     *     per_page:int,
     *     total:int
     * }  $meta
     */
    public static function paginatedWithMeta(mixed $items, array $meta): JsonResponse
    {
        return response()->json([
            'data' => $items,
            'meta' => $meta,
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
