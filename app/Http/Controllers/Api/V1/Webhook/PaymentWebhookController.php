<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Webhook;

use App\Application\Webhook\Commands\EnqueuePaymentWebhookCommand;
use App\Application\Webhook\Commands\EnqueuePaymentWebhookHandler;
use App\Http\Controllers\Controller;
use App\Services\Webhook\WebhookIngressException;
use App\Support\Api\ApiResponse;
use App\Support\Data\JsonPayload;
use App\Support\Data\TypedValue;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class PaymentWebhookController extends Controller
{
    /**
     * Create controller instance.
     */
    public function __construct(private readonly EnqueuePaymentWebhookHandler $enqueuePaymentWebhookHandler) {}

    /**
     * Queue payment webhook processing.
     */
    public function __invoke(Request $request): Response
    {
        $signature = (string) $request->header('X-Signature', '');

        if ($signature === '') {
            throw new BadRequestHttpException('Missing X-Signature header.');
        }

        try {
            $this->enqueuePaymentWebhookHandler->handle(
                new EnqueuePaymentWebhookCommand(
                    payload: JsonPayload::fromArray(TypedValue::associativeArray($request->all())),
                    signature: $signature,
                    receivedAtIso8601: now()->toIso8601String(),
                ),
            );
        } catch (WebhookIngressException $exception) {
            return ApiResponse::error($exception->getMessage(), $exception->statusCode());
        }

        return ApiResponse::data(['queued' => true], Response::HTTP_ACCEPTED);
    }
}
