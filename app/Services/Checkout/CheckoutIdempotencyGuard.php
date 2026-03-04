<?php

declare(strict_types=1);

namespace App\Services\Checkout;

use App\Domain\Exceptions\CheckoutException;
use App\Models\Cart;
use App\Models\CheckoutIdempotency;
use App\Models\Order;
use App\Services\Checkout\Dto\CheckoutIdempotencyResolutionDto;
use App\Support\Data\TypedValue;

final class CheckoutIdempotencyGuard
{
    public function resolve(
        Cart $lockedCart,
        string $scopeKey,
        string $idempotencyKey,
        string $requestHash,
    ): CheckoutIdempotencyResolutionDto {
        $idempotency = CheckoutIdempotency::query()
            ->where('scope_key', $scopeKey)
            ->where('idempotency_key', $idempotencyKey)
            ->lockForUpdate()
            ->first();

        if (! $idempotency instanceof CheckoutIdempotency) {
            return new CheckoutIdempotencyResolutionDto(
                idempotency: CheckoutIdempotency::query()->create([
                    'scope_key' => $scopeKey,
                    'idempotency_key' => $idempotencyKey,
                    'cart_id' => $lockedCart->id,
                    'request_hash' => $requestHash,
                    'expires_at' => now()->addMinutes(30),
                ]),
                existingOrder: null,
            );
        }

        $idempotencyExpiresAt = $idempotency->getRawOriginal('expires_at');

        if ($idempotencyExpiresAt !== null && now()->isAfter(TypedValue::string($idempotencyExpiresAt))) {
            $idempotency->update([
                'cart_id' => $lockedCart->id,
                'order_id' => null,
                'request_hash' => $requestHash,
                'expires_at' => now()->addMinutes(30),
            ]);

            return new CheckoutIdempotencyResolutionDto(
                idempotency: $idempotency,
                existingOrder: null,
            );
        }

        if ($idempotency->request_hash !== $requestHash) {
            throw CheckoutException::idempotencyPayloadMismatch();
        }

        if ($idempotency->order_id !== null) {
            /** @var Order $existingOrder */
            $existingOrder = Order::query()->with(['items', 'payments', 'shipments'])->findOrFail($idempotency->order_id);

            return new CheckoutIdempotencyResolutionDto(
                idempotency: $idempotency,
                existingOrder: $existingOrder,
            );
        }

        if ($idempotency->cart_id !== null && $idempotency->cart_id !== $lockedCart->id) {
            throw CheckoutException::idempotencyCartMismatch();
        }

        return new CheckoutIdempotencyResolutionDto(
            idempotency: $idempotency,
            existingOrder: null,
        );
    }
}
