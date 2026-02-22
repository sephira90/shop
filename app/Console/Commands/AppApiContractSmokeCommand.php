<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\RoleName;
use App\Models\ProductVariant;
use App\Models\User;
use App\Support\Observability\ObservabilityService;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\RoleSeeder;
use DomainException;
use Illuminate\Console\Command;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AppApiContractSmokeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:api-contract-smoke
        {--persist : Persist smoke records instead of rolling them back in production.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run API contract smoke checks for core /api/v1 endpoints.';

    /**
     * Create command instance.
     */
    public function __construct(
        private readonly HttpKernel $httpKernel,
        private readonly ObservabilityService $observabilityService,
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $checks = $this->runChecksWithPersistenceGuard();
        } catch (\Throwable $exception) {
            $this->error('API contract smoke failed: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->table(
            ['check', 'status', 'result'],
            array_map(static fn (array $check): array => [
                $check['name'],
                (string) $check['status'],
                $check['result'],
            ], $checks),
        );

        $this->info('API contract smoke checks passed.');

        return self::SUCCESS;
    }

    /**
     * Run checks and roll back smoke writes in production by default.
     *
     * @return list<array{name: string, status: int, result: string}>
     */
    private function runChecksWithPersistenceGuard(): array
    {
        if (! $this->shouldRollbackSmokeData()) {
            return $this->runChecks();
        }

        DB::beginTransaction();

        try {
            $checks = $this->runChecks();
            DB::rollBack();
        } catch (\Throwable $exception) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            throw $exception;
        }

        $this->warn('Production safeguard: smoke data rolled back. Use --persist to keep records.');

        return $checks;
    }

    /**
     * Run the full set of API contract checks.
     *
     * @return list<array{name: string, status: int, result: string}>
     */
    private function runChecks(): array
    {
        $this->seedRequiredData();
        $adminToken = $this->resolveManagerToken();

        return [
            $this->checkCatalogList(),
            $this->checkCatalogNotFound(),
            $this->checkCartShow(),
            $this->checkCheckoutPlaceOrder(),
            $this->checkCheckoutMissingIdempotency(),
            $this->checkAdminProductsList($adminToken),
            $this->checkAdminProductsValidation($adminToken),
            $this->checkPaymentWebhookMissingSignature(),
        ];
    }

    /**
     * Determine whether smoke writes must be rolled back.
     */
    private function shouldRollbackSmokeData(): bool
    {
        return (string) config('app.env') === 'production' && ! (bool) $this->option('persist');
    }

    /**
     * Seed minimum data required by smoke checks.
     */
    private function seedRequiredData(): void
    {
        app(RoleSeeder::class)->run();
        app(CatalogSeeder::class)->run();
    }

    /**
     * Resolve manager token for admin API checks.
     */
    private function resolveManagerToken(): string
    {
        $manager = User::query()->firstOrCreate(
            ['email' => 'api.contract.manager@shop.local'],
            [
                'first_name' => 'Api',
                'last_name' => 'Contract',
                'name' => 'Api Contract',
                'password' => Hash::make(Str::random(32)),
                'email_verified_at' => now(),
                'is_active' => true,
            ],
        );

        if ($manager->email_verified_at === null) {
            $manager->forceFill(['email_verified_at' => now()])->save();
        }

        if (! $manager->is_active) {
            $manager->forceFill(['is_active' => true])->save();
        }

        $manager->assignRole(RoleName::MANAGER);

        return $manager->createToken('api-contract-smoke')->plainTextToken;
    }

    /**
     * Check catalog list success envelope with pagination meta.
     *
     * @return array{name: string, status: int, result: string}
     */
    private function checkCatalogList(): array
    {
        $response = $this->request('GET', '/api/v1/catalog/products?per_page=5');
        $json = $response['json'];

        $this->assertStatus($response['status'], HttpResponse::HTTP_OK, 'catalog list');
        $this->assertHasKeys($json, ['data', 'meta'], 'catalog list');
        $this->assertMetaShape($json['meta'] ?? null, 'catalog list');

        return [
            'name' => 'catalog_products_list',
            'status' => $response['status'],
            'result' => 'ok',
        ];
    }

    /**
     * Check not found product error envelope.
     *
     * @return array{name: string, status: int, result: string}
     */
    private function checkCatalogNotFound(): array
    {
        $response = $this->request('GET', '/api/v1/catalog/products/does-not-exist-smoke');
        $json = $response['json'];

        $this->assertStatus($response['status'], HttpResponse::HTTP_NOT_FOUND, 'catalog show missing');
        $this->assertErrorEnvelope($json, 'catalog show missing');

        return [
            'name' => 'catalog_product_not_found',
            'status' => $response['status'],
            'result' => 'ok',
        ];
    }

    /**
     * Check cart show response envelope.
     *
     * @return array{name: string, status: int, result: string}
     */
    private function checkCartShow(): array
    {
        $response = $this->request('GET', '/api/v1/cart');
        $json = $response['json'];

        $this->assertStatus($response['status'], HttpResponse::HTTP_OK, 'cart show');
        $this->assertHasKeys($json, ['data'], 'cart show');
        $this->assertHasKeys((array) ($json['data'] ?? []), ['id', 'currency', 'items', 'summary'], 'cart show payload');

        return [
            'name' => 'cart_show',
            'status' => $response['status'],
            'result' => 'ok',
        ];
    }

    /**
     * Check checkout missing idempotency header error envelope.
     *
     * @return array{name: string, status: int, result: string}
     */
    private function checkCheckoutMissingIdempotency(): array
    {
        $response = $this->request('POST', '/api/v1/checkout/place-order', [
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
        $json = $response['json'];

        $this->assertStatus($response['status'], HttpResponse::HTTP_BAD_REQUEST, 'checkout missing idempotency');
        $this->assertErrorEnvelope($json, 'checkout missing idempotency');

        return [
            'name' => 'checkout_missing_idempotency',
            'status' => $response['status'],
            'result' => 'ok',
        ];
    }

    /**
     * Check checkout place-order success envelope shape.
     *
     * @return array{name: string, status: int, result: string}
     */
    private function checkCheckoutPlaceOrder(): array
    {
        $variantId = (int) (ProductVariant::query()->value('id') ?? 0);
        if ($variantId <= 0) {
            throw new DomainException('checkout place-order precondition failed: no product variant found.');
        }

        $guestToken = 'contract-smoke-guest-'.Str::lower(Str::random(12));
        $checkoutKey = 'contract-smoke-checkout-'.Str::lower(Str::random(12));

        $cartResponse = $this->request('POST', '/api/v1/cart/items', [
            'product_variant_id' => $variantId,
            'quantity' => 1,
            'guest_token' => $guestToken,
        ]);
        $this->assertStatus($cartResponse['status'], HttpResponse::HTTP_OK, 'checkout cart setup');

        $response = $this->request(
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
        $json = $response['json'];

        $this->assertStatus($response['status'], HttpResponse::HTTP_CREATED, 'checkout place-order');
        $this->assertHasKeys($json, ['data'], 'checkout place-order');

        $data = (array) ($json['data'] ?? []);
        $this->assertHasKeys($data, ['id', 'order_number', 'payment'], 'checkout place-order data');

        if (array_key_exists('payment', $json)) {
            throw new DomainException('checkout place-order should not expose top-level "payment".');
        }

        $payment = $data['payment'] ?? null;
        if (! is_array($payment)) {
            throw new DomainException('checkout place-order payment payload is not an object.');
        }

        $this->assertHasKeys($payment, ['payment_id', 'transaction_id', 'status', 'payload'], 'checkout place-order payment');

        return [
            'name' => 'checkout_place_order',
            'status' => $response['status'],
            'result' => 'ok',
        ];
    }

    /**
     * Check admin products list success envelope.
     *
     * @return array{name: string, status: int, result: string}
     */
    private function checkAdminProductsList(string $token): array
    {
        $response = $this->request('GET', '/api/v1/admin/products?per_page=10', [], [
            'Authorization' => 'Bearer '.$token,
        ]);
        $json = $response['json'];

        $this->assertStatus($response['status'], HttpResponse::HTTP_OK, 'admin products list');
        $this->assertHasKeys($json, ['data', 'meta'], 'admin products list');
        $this->assertMetaShape($json['meta'] ?? null, 'admin products list');

        return [
            'name' => 'admin_products_list',
            'status' => $response['status'],
            'result' => 'ok',
        ];
    }

    /**
     * Check admin products validation error envelope.
     *
     * @return array{name: string, status: int, result: string}
     */
    private function checkAdminProductsValidation(string $token): array
    {
        $response = $this->request('GET', '/api/v1/admin/products?per_page=0', [], [
            'Authorization' => 'Bearer '.$token,
        ]);
        $json = $response['json'];

        $this->assertStatus($response['status'], HttpResponse::HTTP_UNPROCESSABLE_ENTITY, 'admin products validation');
        $this->assertErrorEnvelope($json, 'admin products validation');

        return [
            'name' => 'admin_products_validation',
            'status' => $response['status'],
            'result' => 'ok',
        ];
    }

    /**
     * Check payment webhook missing signature error envelope.
     *
     * @return array{name: string, status: int, result: string}
     */
    private function checkPaymentWebhookMissingSignature(): array
    {
        $response = $this->request('POST', '/api/v1/webhooks/payment', [
            'event_id' => 'evt-contract-smoke',
            'transaction_id' => 'tx-contract-smoke',
            'status' => 'paid',
        ]);
        $json = $response['json'];

        $this->assertStatus($response['status'], HttpResponse::HTTP_BAD_REQUEST, 'payment webhook missing signature');
        $this->assertErrorEnvelope($json, 'payment webhook missing signature');

        return [
            'name' => 'payment_webhook_missing_signature',
            'status' => $response['status'],
            'result' => 'ok',
        ];
    }

    /**
     * Run one JSON API request through HTTP kernel.
     *
     * @param  array<string, mixed>  $payload
     * @param  array<string, string>  $headers
     * @return array{status:int,json:array<string,mixed>}
     */
    private function request(string $method, string $uri, array $payload = [], array $headers = []): array
    {
        $startedAt = hrtime(true);

        $server = [
            'HTTP_ACCEPT' => 'application/json',
            'CONTENT_TYPE' => 'application/json',
        ];

        foreach ($headers as $name => $value) {
            $normalized = 'HTTP_'.strtoupper(str_replace('-', '_', $name));
            if ($normalized === 'HTTP_CONTENT_TYPE') {
                $normalized = 'CONTENT_TYPE';
            }
            $server[$normalized] = $value;
        }

        $content = strtoupper($method) === 'GET'
            ? null
            : json_encode($payload, JSON_THROW_ON_ERROR);

        $request = Request::create($uri, strtoupper($method), [], [], [], $server, $content);

        if (strtoupper($method) === 'GET' && $payload !== []) {
            $request->query->add($payload);
        }

        $response = $this->httpKernel->handle($request);
        $this->httpKernel->terminate($request, $response);

        $durationMs = (hrtime(true) - $startedAt) / 1_000_000;
        $this->observabilityService->apiRequest(
            method: strtoupper($method),
            path: '/'.ltrim((string) $request->path(), '/'),
            status: $response->getStatusCode(),
            durationMs: $durationMs,
        );

        $decoded = json_decode((string) $response->getContent(), true);
        $json = is_array($decoded) ? $decoded : [];

        return [
            'status' => $response->getStatusCode(),
            'json' => $json,
        ];
    }

    /**
     * Assert HTTP status code.
     */
    private function assertStatus(int $actual, int $expected, string $scope): void
    {
        if ($actual !== $expected) {
            throw new DomainException(sprintf('%s expected status %d, got %d.', $scope, $expected, $actual));
        }
    }

    /**
     * Assert required keys exist in payload.
     *
     * @param  array<string, mixed>  $payload
     * @param  list<string>  $keys
     */
    private function assertHasKeys(array $payload, array $keys, string $scope): void
    {
        foreach ($keys as $key) {
            if (! array_key_exists($key, $payload)) {
                throw new DomainException(sprintf('%s missing required key "%s".', $scope, $key));
            }
        }
    }

    /**
     * Assert standard error envelope shape.
     *
     * @param  array<string, mixed>  $payload
     */
    private function assertErrorEnvelope(array $payload, string $scope): void
    {
        $this->assertHasKeys($payload, ['error'], $scope);

        if (! is_array($payload['error'])) {
            throw new DomainException(sprintf('%s error payload is not an object.', $scope));
        }

        if (! array_key_exists('message', $payload['error'])) {
            throw new DomainException(sprintf('%s error payload missing "message".', $scope));
        }
    }

    /**
     * Assert paginated meta payload shape.
     */
    private function assertMetaShape(mixed $meta, string $scope): void
    {
        if (! is_array($meta)) {
            throw new DomainException(sprintf('%s meta payload is not an object.', $scope));
        }

        $this->assertHasKeys($meta, ['current_page', 'last_page', 'per_page', 'total'], $scope.' meta');
    }
}
