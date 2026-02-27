<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\RoleName;
use App\Models\User;
use App\Support\Smoke\Api\ApiSmokeAssertions;
use App\Support\Smoke\Api\ApiSmokeHttpClient;
use App\Support\Smoke\ApiContract\ApiContractScenario;
use App\Support\Smoke\ApiContract\ApiContractSmokeContext;
use App\Support\Smoke\ApiContract\Scenarios\AdminProductsApiContractScenario;
use App\Support\Smoke\ApiContract\Scenarios\CartApiContractScenario;
use App\Support\Smoke\ApiContract\Scenarios\CatalogApiContractScenario;
use App\Support\Smoke\ApiContract\Scenarios\CheckoutApiContractScenario;
use App\Support\Smoke\ApiContract\Scenarios\PaymentWebhookApiContractScenario;
use App\Support\Smoke\ApiContract\Scenarios\ShippingWebhookApiContractScenario;
use App\Support\Smoke\SmokeCheckResult;
use App\Support\Smoke\SmokePersistenceGuard;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Console\Command;
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
        private readonly ApiSmokeHttpClient $apiClient,
        private readonly ApiSmokeAssertions $assertions,
        private readonly SmokePersistenceGuard $persistenceGuard,
        private readonly CatalogApiContractScenario $catalogScenario,
        private readonly CartApiContractScenario $cartScenario,
        private readonly CheckoutApiContractScenario $checkoutScenario,
        private readonly AdminProductsApiContractScenario $adminProductsScenario,
        private readonly PaymentWebhookApiContractScenario $paymentWebhookScenario,
        private readonly ShippingWebhookApiContractScenario $shippingWebhookScenario,
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $execution = $this->persistenceGuard->run(
                $this->shouldRollbackSmokeData(),
                fn (): array => $this->runChecks(),
            );
        } catch (\Throwable $exception) {
            $this->error('API contract smoke failed: '.$exception->getMessage());

            return self::FAILURE;
        }

        /** @var list<SmokeCheckResult> $checks */
        $checks = $execution['result'];

        $this->table(
            ['check', 'status', 'result'],
            array_map(static fn (SmokeCheckResult $check): array => $check->toTableRow(), $checks),
        );

        if ($execution['rolled_back']) {
            $this->warn('Production safeguard: smoke data rolled back. Use --persist to keep records.');
        }

        $this->info('API contract smoke checks passed.');

        return self::SUCCESS;
    }

    /**
     * Run the full set of API contract checks.
     *
     * @return list<SmokeCheckResult>
     */
    private function runChecks(): array
    {
        $this->seedRequiredData();

        $context = new ApiContractSmokeContext($this->resolveManagerToken());
        $checks = [];

        foreach ($this->scenarios() as $scenario) {
            $checks = array_merge($checks, $scenario->run($this->apiClient, $this->assertions, $context));
        }

        return $checks;
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
     * Resolve ordered API contract smoke scenarios.
     *
     * @return list<ApiContractScenario>
     */
    private function scenarios(): array
    {
        return [
            $this->catalogScenario,
            $this->cartScenario,
            $this->checkoutScenario,
            $this->adminProductsScenario,
            $this->paymentWebhookScenario,
            $this->shippingWebhookScenario,
        ];
    }
}
