<?php

declare(strict_types=1);

namespace App\Services\Checkout;

use App\Domain\Exceptions\CheckoutException;
use App\Enums\CartStatus;
use App\Events\OrderPlaced;
use App\Models\Order;
use App\Services\Checkout\Dto\CheckoutOrderFinalizationInputDto;

final class CheckoutOrderFinalizer
{
    public function finalize(CheckoutOrderFinalizationInputDto $input): Order
    {
        $input->lockedCart->update(['status' => CartStatus::CHECKED_OUT->value]);

        if ($input->discountContext->coupon !== null) {
            $input->discountContext->coupon->increment('redeemed_count');
        }

        if ($input->discountContext->promotion !== null) {
            $input->discountContext->promotion->increment('usage_count');
        }

        $input->idempotency->update([
            'cart_id' => $input->lockedCart->id,
            'request_hash' => $input->requestHash,
            'order_id' => $input->order->id,
            'expires_at' => now()->addHours(24),
        ]);

        event(new OrderPlaced($input->order));

        $refreshedOrder = $input->order->fresh(['items', 'payments', 'shipments']);

        if (! $refreshedOrder instanceof Order) {
            throw CheckoutException::orderNotFoundAfterFinalization();
        }

        return $refreshedOrder;
    }
}
