<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Application\Checkout\Commands\InitiateCheckoutPaymentCommand;
use App\Application\Checkout\Commands\InitiateCheckoutPaymentHandler;
use App\Application\Checkout\Commands\PlaceCheckoutOrderCommand;
use App\Application\Checkout\Commands\PlaceCheckoutOrderHandler;
use App\Http\Controllers\Concerns\ResolvesAuthenticatedUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\Checkout\InitiatePaymentRequest;
use App\Http\Requests\Checkout\PlaceOrderRequest;
use App\Models\Order;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class CheckoutController extends Controller
{
    use ResolvesAuthenticatedUser;

    /**
     * Create controller instance.
     */
    public function __construct(
        private readonly PlaceCheckoutOrderHandler $placeCheckoutOrderHandler,
        private readonly InitiateCheckoutPaymentHandler $initiateCheckoutPaymentHandler,
    ) {}

    /**
     * Place order from active cart.
     */
    public function placeOrder(PlaceOrderRequest $request): JsonResponse
    {
        $input = $request->toDto();
        $currentUser = $this->resolveAuthenticatedUser($request);
        $command = new PlaceCheckoutOrderCommand($input, $request->idempotencyKey(), $currentUser);
        $result = $this->placeCheckoutOrderHandler->handle($command);

        return ApiResponse::data($result->toArray(), Response::HTTP_CREATED);
    }

    /**
     * Initiate payment for order.
     */
    public function pay(InitiatePaymentRequest $request, Order $order): JsonResponse
    {
        $this->authorize('view', $order);

        $payment = $this->initiateCheckoutPaymentHandler->handle(
            new InitiateCheckoutPaymentCommand($order, $request->idempotencyKey())
        );

        return ApiResponse::data($payment->toArray());
    }
}
