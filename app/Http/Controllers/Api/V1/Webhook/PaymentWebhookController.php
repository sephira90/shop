<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Webhook;

use App\Contracts\PaymentGatewayInterface;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessPaymentWebhookJob;
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
            return response()->json([
                'error' => [
                    'message' => 'Missing X-Signature header.',
                ],
            ], Response::HTTP_BAD_REQUEST);
        }

        $payload = $request->all();
        if (! $this->paymentGateway->verifyWebhookSignature($payload, $signature)) {
            return response()->json([
                'error' => [
                    'message' => 'Invalid webhook signature.',
                ],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($this->paymentGateway->extractEventId($payload) === '') {
            return response()->json([
                'error' => [
                    'message' => 'Webhook event id is required.',
                ],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($this->paymentGateway->extractTransactionId($payload) === '') {
            return response()->json([
                'error' => [
                    'message' => 'Payment transaction id is required.',
                ],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            ProcessPaymentWebhookJob::dispatch($payload, $signature);
        } catch (DomainException $exception) {
            return response()->json([
                'error' => [
                    'message' => $exception->getMessage(),
                ],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json([
            'data' => [
                'queued' => true,
            ],
        ], Response::HTTP_ACCEPTED);
    }
}
