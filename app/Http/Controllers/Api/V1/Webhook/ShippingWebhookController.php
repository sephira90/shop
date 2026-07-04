<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Webhook;

use App\Application\Webhook\Commands\EnqueueShippingWebhookCommand;
use App\Application\Webhook\Commands\EnqueueShippingWebhookHandler;
use App\Http\Controllers\Controller;
use App\Services\Webhook\WebhookIngressException;
use App\Support\Api\ApiResponse;
use App\Support\Data\JsonPayload;
use App\Support\Data\TypedValue;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ShippingWebhookController extends Controller
{
    /**
     * Create controller instance.
     */
    public function __construct(private readonly EnqueueShippingWebhookHandler $enqueueShippingWebhookHandler) {}

    /**
     * Process shipping webhook payload.
     */
    public function __invoke(Request $request): Response
    {
        $signature = (string) $request->header('X-Signature', '');

        if ($signature === '') {
            throw new BadRequestHttpException('Missing X-Signature header.');
        }

        try {
            $this->enqueueShippingWebhookHandler->handle(
                new EnqueueShippingWebhookCommand(
                    payload: JsonPayload::fromArray(TypedValue::associativeArray($request->all())),
                    signature: $signature,
                    receivedAtIso8601: now()->toIso8601String(),
                ),
            );
        } catch (WebhookIngressException $exception) {
            return ApiResponse::error($exception->getMessage(), $exception->statusCode());
        }

        return ApiResponse::data(['processed' => true]);
    }
}
