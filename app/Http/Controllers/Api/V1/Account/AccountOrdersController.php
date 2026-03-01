<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Account;

use App\Application\Account\Orders\Queries\GetAccountOrderDetailHandler;
use App\Application\Account\Orders\Queries\GetAccountOrderDetailQuery;
use App\Application\Account\Orders\Queries\GetAccountOrdersSummaryHandler;
use App\Application\Account\Orders\Queries\GetAccountOrdersSummaryQuery;
use App\Application\Account\Orders\Queries\PaginateAccountOrdersHandler;
use App\Application\Account\Orders\Queries\PaginateAccountOrdersQuery;
use App\Application\Account\Orders\Queries\PaginateLegacyAccountOrdersHandler;
use App\Application\Account\Orders\Queries\PaginateLegacyAccountOrdersQuery;
use App\Http\Controllers\Concerns\ResolvesAuthenticatedUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\Account\AccountOrderIndexRequest;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class AccountOrdersController extends Controller
{
    use ResolvesAuthenticatedUser;

    public function __construct(
        private readonly PaginateAccountOrdersHandler $paginateAccountOrdersHandler,
        private readonly PaginateLegacyAccountOrdersHandler $paginateLegacyAccountOrdersHandler,
        private readonly GetAccountOrderDetailHandler $getAccountOrderDetailHandler,
        private readonly GetAccountOrdersSummaryHandler $getAccountOrdersSummaryHandler,
    ) {}

    public function index(AccountOrderIndexRequest $request): JsonResponse
    {
        $currentUser = $this->requireAuthenticatedUser($request);

        $orders = $this->paginateAccountOrdersHandler->handle(
            new PaginateAccountOrdersQuery($currentUser, $request->filter())
        );

        return ApiResponse::paginatedWithMeta($orders->itemsToArray(), $orders->metaToArray());
    }

    public function legacyIndex(AccountOrderIndexRequest $request): JsonResponse
    {
        $currentUser = $this->requireAuthenticatedUser($request);

        $orders = $this->paginateLegacyAccountOrdersHandler->handle(
            new PaginateLegacyAccountOrdersQuery($currentUser, $request->filter())
        );

        return ApiResponse::paginatedWithMeta($orders->itemsToArray(), $orders->metaToArray());
    }

    public function show(Request $request, string $order): JsonResponse
    {
        $currentUser = $this->requireAuthenticatedUser($request);

        $detail = $this->getAccountOrderDetailHandler->handle(
            new GetAccountOrderDetailQuery($currentUser, $order)
        );

        if ($detail === null) {
            return ApiResponse::error('Order not found.', Response::HTTP_NOT_FOUND);
        }

        return ApiResponse::data($detail->toArray());
    }

    public function summary(Request $request): JsonResponse
    {
        $currentUser = $this->requireAuthenticatedUser($request);

        $summary = $this->getAccountOrdersSummaryHandler->handle(
            new GetAccountOrdersSummaryQuery($currentUser)
        );

        return ApiResponse::data($summary->toArray());
    }
}
