<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\PaymentGatewayInterface;
use App\Contracts\ShippingGatewayInterface;
use App\Support\Data\TypedValue;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

final class GatewayServiceProvider extends ServiceProvider
{
    /**
     * Register gateway driver bindings.
     */
    public function register(): void
    {
        $this->app->bind(
            PaymentGatewayInterface::class,
            fn (): PaymentGatewayInterface => $this->resolveGatewayDriver('payment', PaymentGatewayInterface::class),
        );

        $this->app->bind(
            ShippingGatewayInterface::class,
            fn (): ShippingGatewayInterface => $this->resolveGatewayDriver('shipping', ShippingGatewayInterface::class),
        );
    }

    /**
     * Resolve configured gateway implementation from `{domain}.driver`.
     *
     * @template TGateway of object
     *
     * @param  'payment'|'shipping'  $domain
     * @param  class-string<TGateway>  $contract
     * @return TGateway
     */
    private function resolveGatewayDriver(string $domain, string $contract): object
    {
        $driver = TypedValue::string(config($domain.'.driver'));
        $drivers = config($domain.'.drivers');

        if (! is_array($drivers)) {
            throw new InvalidArgumentException(sprintf('Invalid %s driver map configuration.', $domain));
        }

        $gatewayClass = $drivers[$driver] ?? null;

        if (! is_string($gatewayClass) || $gatewayClass === '') {
            throw new InvalidArgumentException(sprintf('Unsupported %s driver [%s].', $domain, $driver));
        }

        $gateway = $this->app->make($gatewayClass);

        if (! $gateway instanceof $contract) {
            throw new InvalidArgumentException(sprintf(
                '%s driver [%s] must implement %s.',
                ucfirst($domain),
                $driver,
                $contract,
            ));
        }

        return $gateway;
    }
}
