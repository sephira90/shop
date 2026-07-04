<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use App\Contracts\PaymentGatewayInterface;
use App\Contracts\ShippingGatewayInterface;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class GatewayPayloadBoundaryGuardrailTest extends TestCase
{
    /**
     * Gateway payload blobs must stay provider-operational only: cardholder data,
     * CVV, customer PII, free-form names must never enter the persisted payload
     * column. Locks audit item 82 against future real-provider adapters.
     */
    #[DataProvider('gatewayImplementations')]
    public function test_gateway_adapters_do_not_reference_pii_literals(string $relativePath): void
    {
        $absolutePath = base_path($relativePath);
        $this->assertFileExists($absolutePath);

        $contents = (string) File::get($absolutePath);

        $piiLiterals = [
            "'card'",
            '"card"',
            "'card_number'",
            '"card_number"',
            "'pan'",
            '"pan"',
            "'cvv'",
            '"cvv"',
            "'cvc'",
            '"cvc"',
            "'ssn'",
            '"ssn"',
            "'password'",
            '"password"',
            "'recipient_name'",
            '"recipient_name"',
        ];

        foreach ($piiLiterals as $literal) {
            $this->assertStringNotContainsString(
                $literal,
                $contents,
                $relativePath.' must not reference PII literal '.$literal.' in gateway payload.',
            );
        }
    }

    /**
     * Gateway payload construction must route through JsonPayload so the shape
     * stays normalized and consistent with the persisted column contract.
     */
    #[DataProvider('gatewayImplementations')]
    public function test_gateway_adapters_build_payloads_via_json_payload(string $relativePath): void
    {
        $absolutePath = base_path($relativePath);
        $this->assertFileExists($absolutePath);

        $contents = (string) File::get($absolutePath);

        $this->assertStringContainsString(
            'JsonPayload::fromArray(',
            $contents,
            $relativePath.' must build gateway payload through JsonPayload::fromArray().',
        );
    }

    /**
     * @return array<string, list<string>>
     */
    public static function gatewayImplementations(): array
    {
        $sites = [
            'app/Infrastructure/Payments/FakePaymentGateway.php',
            'app/Infrastructure/Shipping/FakeShippingGateway.php',
        ];

        $cases = [];
        foreach ($sites as $site) {
            $cases[$site] = [$site];
        }

        return $cases;
    }

    public function test_payment_and_shipping_gateway_contracts_are_located_in_contracts(): void
    {
        $this->assertFileExists(base_path('app/Contracts/PaymentGatewayInterface.php'));
        $this->assertFileExists(base_path('app/Contracts/ShippingGatewayInterface.php'));

        $this->assertTrue(interface_exists(PaymentGatewayInterface::class));
        $this->assertTrue(interface_exists(ShippingGatewayInterface::class));
    }
}
