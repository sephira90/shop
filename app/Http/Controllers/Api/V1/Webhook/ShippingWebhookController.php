<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Webhook;

use App\Http\Controllers\Controller;
use App\Services\Shipping\ShippingService;
use App\Support\Api\ApiResponse;
use App\Support\Data\JsonPayload;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ShippingWebhookController extends Controller
{
    /**
     * Create controller instance.
     */
    public function __construct(private readonly ShippingService $shippingService) {}

    /**
     * Process shipping webhook payload.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $signature = (string) $request->header('X-Signature', '');

        if ($signature === '') {
            return ApiResponse::error('Missing X-Signature header.', Response::HTTP_BAD_REQUEST);
        }

        try {
            $this->shippingService->processWebhook(
                JsonPayload::fromArray($request->all()),
                $signature,
                now()->toIso8601String(),
            );
        } catch (DomainException $exception) {
            return ApiResponse::error($exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return ApiResponse::data(['processed' => true]);
    }
}
