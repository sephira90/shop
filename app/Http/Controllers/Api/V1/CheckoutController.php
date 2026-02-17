<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Checkout\PlaceOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Repositories\OrderRepository;
use App\Services\Cart\CartService;
use App\Services\Checkout\CheckoutService;
use App\Services\Payment\PaymentService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckoutController extends Controller
{
    /**
     * Create controller instance.
     */
    public function __construct(
        private readonly CartService $cartService,
        private readonly CheckoutService $checkoutService,
        private readonly PaymentService $paymentService,
        private readonly OrderRepository $orderRepository,
    ) {}

    /**
     * Place order from active cart.
     */
    public function placeOrder(PlaceOrderRequest $request): JsonResponse
    {
        $idempotencyKey = (string) $request->header('Idempotency-Key', '');

        if ($idempotencyKey === '') {
            return response()->json([
                'error' => [
                    'message' => 'Idempotency-Key header is required.',
                ],
            ], Response::HTTP_BAD_REQUEST);
        }

        $payload = $request->validated();
        $cart = $this->cartService->resolveForCheckout($request->user(), $payload['guest_token'] ?? null);

        try {
            $order = $this->checkoutService->placeOrder($cart, $payload, $idempotencyKey, $request->user());
        } catch (DomainException $exception) {
            return response()->json([
                'error' => [
                    'message' => $exception->getMessage(),
                ],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $payment = $this->paymentService->initiate($order, 'checkout-'.$idempotencyKey);

        return response()->json([
            'data' => OrderResource::make($order),
            'payment' => [
                'payment_id' => $payment->id,
                'transaction_id' => $payment->transaction_id,
                'status' => $payment->status?->value,
                'payload' => $payment->payload,
            ],
        ], Response::HTTP_CREATED);
    }

    /**
     * Initiate payment for order.
     */
    public function pay(Request $request, Order $order): JsonResponse
    {
        $this->authorize('view', $order);

        $idempotencyKey = (string) $request->header('Idempotency-Key', 'pay-'.$order->id);
        $payment = $this->paymentService->initiate($order, $idempotencyKey);

        return response()->json([
            'data' => [
                'payment_id' => $payment->id,
                'transaction_id' => $payment->transaction_id,
                'status' => $payment->status?->value,
                'payload' => $payment->payload,
            ],
        ]);
    }

    /**
     * Return current user orders.
     */
    public function myOrders(Request $request): JsonResponse
    {
        $orders = $this->orderRepository->paginateForUser($request->user(), 20);

        return response()->json([
            'data' => OrderResource::collection($orders->items()),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'total' => $orders->total(),
            ],
        ]);
    }
}
