<?php

declare(strict_types=1);

namespace App\Support\Smoke\ApiContract\Scenarios;

use App\Enums\ProductStatus;
use App\Models\ProductVariant;
use App\Support\Smoke\Api\ApiSmokeAssertions;
use App\Support\Smoke\Api\ApiSmokeHttpClient;
use App\Support\Smoke\ApiContract\ApiContractScenario;
use App\Support\Smoke\ApiContract\ApiContractSmokeContext;
use App\Support\Smoke\SmokeCheckResult;
use DomainException;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Str;

final class CheckoutApiContractScenario implements ApiContractScenario
{
    /**
     * Execute checkout API contract checks.
     *
     * @return list<SmokeCheckResult>
     */
    public function run(ApiSmokeHttpClient $client, ApiSmokeAssertions $assertions, ApiContractSmokeContext $context): array
    {
        return [
            $this->checkCheckoutPlaceOrder($client, $assertions),
            $this->checkCheckoutMissingIdempotency($client, $assertions),
        ];
    }

    /**
     * Check checkout place-order success envelope shape.
     */
    private function checkCheckoutPlaceOrder(ApiSmokeHttpClient $client, ApiSmokeAssertions $assertions): SmokeCheckResult
    {
        $variantId = (int) (ProductVariant::query()
            ->where('is_active', true)
            ->whereHas('product', static function ($query): void {
                $query
                    ->where('status', ProductStatus::ACTIVE->value)
                    ->whereNotNull('published_at');
            })
            ->whereHas('inventory', static function ($query): void {
                $query->whereColumn('quantity', '>', 'reserved_quantity');
            })
            ->orderBy('id')
            ->value('id') ?? 0);

        if ($variantId <= 0) {
            throw new DomainException('checkout place-order precondition failed: no product variant found.');
        }

        $guestToken = 'contract-smoke-guest-'.Str::lower(Str::random(12));
        $checkoutKey = 'contract-smoke-checkout-'.Str::lower(Str::random(12));

        $cartResponse = $client->request('POST', '/api/v1/cart/items', [
            'product_variant_id' => $variantId,
            'quantity' => 1,
            'guest_token' => $guestToken,
        ]);
        $assertions->assertStatus($cartResponse->status, HttpResponse::HTTP_OK, 'checkout cart setup');

        $response = $client->request(
            'POST',
            '/api/v1/checkout/place-order',
            [
                'guest_token' => $guestToken,
                'email' => 'contract@example.com',
                'billing_address' => [
                    'line1' => '1 Main Street',
                    'city' => 'New York',
                    'country' => 'US',
                    'postcode' => '10001',
                ],
                'shipping_address' => [
                    'line1' => '1 Main Street',
                    'city' => 'New York',
                    'country' => 'US',
                    'postcode' => '10001',
                ],
            ],
            [
                'Idempotency-Key' => $checkoutKey,
            ],
        );

        $assertions->assertStatus($response->status, HttpResponse::HTTP_CREATED, 'checkout place-order');
        $assertions->assertHasKeys($response->json, ['data'], 'checkout place-order');

        $data = (array) ($response->json['data'] ?? []);
        $assertions->assertHasKeys($data, ['id', 'order_number', 'payment'], 'checkout place-order data');

        if (array_key_exists('payment', $response->json)) {
            throw new DomainException('checkout place-order should not expose top-level "payment".');
        }

        $payment = $data['payment'] ?? null;
        if (! is_array($payment)) {
            throw new DomainException('checkout place-order payment payload is not an object.');
        }

        $assertions->assertHasKeys($payment, ['payment_id', 'transaction_id', 'status', 'payload'], 'checkout place-order payment');

        return new SmokeCheckResult('checkout_place_order', $response->status);
    }

    /**
     * Check checkout missing idempotency header error envelope.
     */
    private function checkCheckoutMissingIdempotency(ApiSmokeHttpClient $client, ApiSmokeAssertions $assertions): SmokeCheckResult
    {
        $response = $client->request('POST', '/api/v1/checkout/place-order', [
            'guest_token' => 'contract-smoke-guest-token',
            'email' => 'contract@example.com',
            'billing_address' => [
                'line1' => '1 Main Street',
                'city' => 'New York',
                'country' => 'US',
                'postcode' => '10001',
            ],
            'shipping_address' => [
                'line1' => '1 Main Street',
                'city' => 'New York',
                'country' => 'US',
                'postcode' => '10001',
            ],
        ]);

        $assertions->assertStatus($response->status, HttpResponse::HTTP_BAD_REQUEST, 'checkout missing idempotency');
        $assertions->assertErrorEnvelope($response->json, 'checkout missing idempotency');

        return new SmokeCheckResult('checkout_missing_idempotency', $response->status);
    }
}
