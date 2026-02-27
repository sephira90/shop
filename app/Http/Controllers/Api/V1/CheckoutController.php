<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Application\Checkout\Commands\InitiateCheckoutPaymentCommand;
use App\Application\Checkout\Commands\InitiateCheckoutPaymentHandler;
use App\Application\Checkout\Commands\PlaceCheckoutOrderCommand;
use App\Application\Checkout\Commands\PlaceCheckoutOrderHandler;
use App\Application\Checkout\Queries\GetMyOrdersSummaryHandler;
use App\Application\Checkout\Queries\GetMyOrdersSummaryQuery;
use App\Application\Checkout\Queries\PaginateMyOrdersHandler;
use App\Application\Checkout\Queries\PaginateMyOrdersQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests\Account\AccountOrderIndexRequest;
use App\Http\Requests\Checkout\PlaceOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\User;
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
        private readonly PlaceCheckoutOrderHandler $placeCheckoutOrderHandler,
        private readonly PaginateMyOrdersHandler $paginateMyOrdersHandler,
        private readonly GetMyOrdersSummaryHandler $getMyOrdersSummaryHandler,
        private readonly InitiateCheckoutPaymentHandler $initiateCheckoutPaymentHandler,
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

        $input = $request->toDto();
        $currentUser = $this->resolveCurrentUser($request);
        $command = new PlaceCheckoutOrderCommand($input, $idempotencyKey, $currentUser);

        try {
            $result = $this->placeCheckoutOrderHandler->handle($command);
        } catch (DomainException $exception) {
            return ApiResponse::error($exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $orderData = OrderResource::make($result->order)->toArray($request);

        return ApiResponse::data($result->toArray($orderData), Response::HTTP_CREATED);
    }

    /**
     * Initiate payment for order.
     */
    public function pay(Request $request, Order $order): JsonResponse
    {
        $this->authorize('view', $order);

        $idempotencyKey = (string) $request->header('Idempotency-Key', 'pay-'.$order->id);
        $payment = $this->initiateCheckoutPaymentHandler->handle(
            new InitiateCheckoutPaymentCommand($order, $idempotencyKey)
        );

        return ApiResponse::data($payment->toArray());
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

        $orders = $this->paginateMyOrdersHandler->handle(
            new PaginateMyOrdersQuery($currentUser, $request->filter())
        );

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

        return ApiResponse::data(
            $this->getMyOrdersSummaryHandler->handle(new GetMyOrdersSummaryQuery($currentUser))->toArray(),
        );
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
