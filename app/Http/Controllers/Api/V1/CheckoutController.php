<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\AccountOrderIndexRequest;
use App\Http\Requests\Checkout\PlaceOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\User;
use App\Repositories\OrderRepository;
use App\Services\Cart\CartService;
use App\Services\Checkout\CheckoutService;
use App\Services\Payment\PaymentService;
use App\Support\Api\ApiResponse;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
            return ApiResponse::error('Idempotency-Key header is required.', Response::HTTP_BAD_REQUEST);
        }

        $payload = $request->validated();
        $currentUser = $this->resolveCurrentUser($request);
        $guestToken = trim((string) ($payload['guest_token'] ?? ''));

        try {
            if ($currentUser !== null && $guestToken !== '') {
                $this->cartService->mergeGuestCart($currentUser, $guestToken);
            }

            $cart = $this->cartService->resolveForCheckout(
                $currentUser,
                $guestToken === '' ? null : $guestToken
            );
            $order = $this->checkoutService->placeOrder($cart, $payload, $idempotencyKey, $currentUser);
        } catch (DomainException $exception) {
            return ApiResponse::error($exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $payment = $this->paymentService->initiate($order, 'checkout-'.$idempotencyKey);
        $orderData = OrderResource::make($order)->toArray($request);

        return ApiResponse::data([
            ...$orderData,
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

        return ApiResponse::data([
            'payment_id' => $payment->id,
            'transaction_id' => $payment->transaction_id,
            'status' => $payment->status?->value,
            'payload' => $payment->payload,
        ]);
    }

    /**
     * Return current user orders.
     */
    public function myOrders(AccountOrderIndexRequest $request): JsonResponse
    {
        $currentUser = $this->resolveCurrentUser($request);

        if (! $currentUser instanceof User) {
            return ApiResponse::error('Authentication is required.', Response::HTTP_UNAUTHORIZED);
        }

        $orders = $this->orderRepository->paginateForUser($currentUser, $request->filter());

        return ApiResponse::paginated(OrderResource::collection($orders->items()), $orders);
    }

    /**
     * Return account order summary metrics for current user.
     */
    public function myOrdersSummary(Request $request): JsonResponse
    {
        $currentUser = $this->resolveCurrentUser($request);

        if (! $currentUser instanceof User) {
            return ApiResponse::error('Authentication is required.', Response::HTTP_UNAUTHORIZED);
        }

        return ApiResponse::data($this->orderRepository->summaryForUser($currentUser));
    }

    /**
     * Resolve currently authenticated user if it is app User model.
     */
    private function resolveCurrentUser(Request $request): ?User
    {
        $authenticated = $request->user() ?? Auth::guard('sanctum')->user();

        return $authenticated instanceof User ? $authenticated : null;
    }
}
