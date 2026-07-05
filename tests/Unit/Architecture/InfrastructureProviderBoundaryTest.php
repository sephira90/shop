<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use App\Domains\Catalog\CatalogServiceProvider;
use App\Domains\Users\UsersServiceProvider;
use App\Providers\ApplicationBindingsServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\EventServiceProvider;
use App\Providers\GatewayServiceProvider;
use App\Providers\MaintenanceServiceProvider;
use App\Providers\ObservabilityServiceProvider;
use App\Providers\OrdersServiceProvider;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;
use ReflectionClass;
use Tests\TestCase;

final class InfrastructureProviderBoundaryTest extends TestCase
{
    public function test_bootstrap_provider_registration_uses_specialized_provider_modules(): void
    {
        /** @var list<class-string> $providers */
        $providers = require base_path('bootstrap/providers.php');

        $this->assertSame([
            ApplicationBindingsServiceProvider::class,
            CatalogServiceProvider::class,
            UsersServiceProvider::class,
            GatewayServiceProvider::class,
            MaintenanceServiceProvider::class,
            ObservabilityServiceProvider::class,
            OrdersServiceProvider::class,
            AppServiceProvider::class,
            EventServiceProvider::class,
        ], $providers);
    }

    public function test_app_service_provider_remains_bootstrap_only(): void
    {
        $reflection = new ReflectionClass(AppServiceProvider::class);
        $registerMethod = $reflection->getMethod('register');

        $this->assertNotSame(
            AppServiceProvider::class,
            $registerMethod->getDeclaringClass()->getName(),
            'AppServiceProvider must not own register()-time bindings.'
        );

        $source = File::get(app_path('Providers/AppServiceProvider.php'));

        $this->assertStringNotContainsString('->bind(', $source);
        $this->assertStringNotContainsString('->singleton(', $source);
        $this->assertStringNotContainsString('->tag(', $source);
    }

    public function test_specialized_provider_modules_own_register_logic(): void
    {
        $providers = [
            ApplicationBindingsServiceProvider::class,
            CatalogServiceProvider::class,
            UsersServiceProvider::class,
            GatewayServiceProvider::class,
            MaintenanceServiceProvider::class,
            ObservabilityServiceProvider::class,
            OrdersServiceProvider::class,
        ];

        foreach ($providers as $providerClass) {
            $reflection = new ReflectionClass($providerClass);

            $this->assertTrue($reflection->isSubclassOf(ServiceProvider::class));
            $this->assertSame(
                $providerClass,
                $reflection->getMethod('register')->getDeclaringClass()->getName(),
                "{$providerClass} must own its register() bindings."
            );
        }
    }
}
