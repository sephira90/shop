<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Webhook;

use App\Http\Controllers\Controller;
use App\Services\Shipping\ShippingService;
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
            return response()->json([
                'error' => [
                    'message' => 'Missing X-Signature header.',
                ],
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $this->shippingService->processWebhook($request->all(), $signature);
        } catch (DomainException $exception) {
            return response()->json([
                'error' => [
                    'message' => $exception->getMessage(),
                ],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json([
            'data' => [
                'processed' => true,
            ],
        ]);
    }
}
