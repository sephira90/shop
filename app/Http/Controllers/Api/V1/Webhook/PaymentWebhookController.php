<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Webhook;

use App\Contracts\PaymentGatewayInterface;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessPaymentWebhookJob;
use App\Support\Api\ApiResponse;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PaymentWebhookController extends Controller
{
    /**
     * Create controller instance.
     */
    public function __construct(private readonly PaymentGatewayInterface $paymentGateway) {}

    /**
     * Queue payment webhook processing.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $signature = (string) $request->header('X-Signature', '');

        if ($signature === '') {
            return ApiResponse::error('Missing X-Signature header.', Response::HTTP_BAD_REQUEST);
        }

        $payload = $request->all();
        if (! $this->paymentGateway->verifyWebhookSignature($payload, $signature)) {
            return ApiResponse::error('Invalid webhook signature.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($this->paymentGateway->extractEventId($payload) === '') {
            return ApiResponse::error('Webhook event id is required.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($this->paymentGateway->extractTransactionId($payload) === '') {
            return ApiResponse::error('Payment transaction id is required.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            ProcessPaymentWebhookJob::dispatch($payload, $signature, now()->toIso8601String());
        } catch (DomainException $exception) {
            return ApiResponse::error($exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return ApiResponse::data(['queued' => true], Response::HTTP_ACCEPTED);
    }
}
