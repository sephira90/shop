<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use App\Domains\Checkout\Application\Dto\CheckoutAddressInputDto;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use Tests\TestCase;

final class AddressPayloadBoundaryGuardrailTest extends TestCase
{
    private const ALLOWED_ADDRESS_KEYS = ['line1', 'city', 'country', 'postcode'];

    /**
     * Address payload produced for persistence must be a closed shape.
     * Drift here widens the PII blast radius (audit item 82).
     */
    public function test_checkout_address_input_dto_emits_closed_address_shape(): void
    {
        $dto = CheckoutAddressInputDto::fromValidated([
            'line1' => '1 Closed Street',
            'city' => 'New York',
            'country' => 'us',
            'postcode' => '10001',
        ]);

        $payload = $dto->toArray();

        $this->assertSame(
            ['line1', 'city', 'country', 'postcode'],
            array_keys($payload),
            'CheckoutAddressInputDto must emit exactly the allowlisted address keys.',
        );

        foreach (self::ALLOWED_ADDRESS_KEYS as $key) {
            $this->assertArrayHasKey($key, $payload);
        }
    }

    public function test_checkout_address_input_dto_to_array_return_type_is_shape_closed(): void
    {
        $reflection = new ReflectionMethod(CheckoutAddressInputDto::class, 'toArray');

        $returnType = $reflection->getReturnType();
        $this->assertNotNull($returnType, 'CheckoutAddressInputDto::toArray() must declare a return type.');

        $docComment = (string) $reflection->getDocComment();
        $this->assertStringContainsString('line1:string', $docComment, 'toArray() must document the line1 key.');
        $this->assertStringContainsString('city:string', $docComment, 'toArray() must document the city key.');
        $this->assertStringContainsString('country:string', $docComment, 'toArray() must document the country key.');
        $this->assertStringContainsString('postcode:string', $docComment, 'toArray() must document the postcode key.');
    }

    /**
     * Address payload construction sites under app/ must keep the closed shape
     * {line1, city, country, postcode}. The boundary is enforced lexically so
     * future adapters cannot smuggle phone/email/notes through the address blob.
     */
    #[DataProvider('addressConstructionSites')]
    public function test_address_construction_sites_keep_closed_shape(string $relativePath): void
    {
        $absolutePath = base_path($relativePath);
        $this->assertFileExists($absolutePath);

        $contents = (string) File::get($absolutePath);
        $lines = explode("\n", $contents);

        $addressStartMarkers = ["'billing_address'", "'shipping_address'", '"billing_address"', '"shipping_address"'];
        $allowedKeys = self::ALLOWED_ADDRESS_KEYS;
        $rejectedKeys = ['phone', 'email', 'notes', 'recipient_name', 'full_name', 'card', 'cvv'];

        for ($i = 0; $i < count($lines); $i++) {
            $line = $lines[$i];

            if (! $this->lineOpensAddressBlob($line, $addressStartMarkers)) {
                continue;
            }

            $blobKeys = $this->collectBlobKeys($lines, $i);

            // Empty blob keys indicate a validation-rules array (list items, no
            // string keys). Those are not address payloads; skip them.
            if ($blobKeys === []) {
                continue;
            }

            foreach ($blobKeys as $key) {
                $this->assertContains(
                    strtolower($key),
                    $allowedKeys,
                    $relativePath.':'.$i.' address blob must contain only allowlisted keys, found '.$key.'.',
                );

                $this->assertNotContains(
                    strtolower($key),
                    $rejectedKeys,
                    $relativePath.':'.$i.' address blob must not contain rejected PII key '.$key.'.',
                );
            }
        }
    }

    /**
     * @param  list<string>  $markers
     */
    private function lineOpensAddressBlob(string $line, array $markers): bool
    {
        $trimmed = trim($line);

        if (! str_contains($trimmed, '=>')) {
            return false;
        }

        if (! str_contains($trimmed, '[')) {
            return false;
        }

        foreach ($markers as $marker) {
            if (str_contains($trimmed, $marker)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Collect quoted keys from the array blob that opens at $startIndex. Handles
     * both multiline and inline forms. Returns an empty list when the blob holds
     * validation rules (list items without string keys), so callers can skip it.
     *
     * @param  list<string>  $lines
     * @return list<string>
     */
    private function collectBlobKeys(array $lines, int $startIndex): array
    {
        $keys = [];
        $opens = substr_count($lines[$startIndex], '[');
        $closes = substr_count($lines[$startIndex], ']');

        $inlineTail = substr($lines[$startIndex], (int) strpos($lines[$startIndex], '['));
        $inlineTail = ltrim($inlineTail, '[');

        $inlineRemainder = $inlineTail;
        while (preg_match("/^\s*'([^']+)'\s*=>/", $inlineRemainder, $matches) === 1
            || preg_match('/^\s*"([^"]+)"\s*=>/', $inlineRemainder, $matches) === 1) {
            $keys[] = $matches[1];
            $inlineRemainder = substr($inlineRemainder, (int) strpos($inlineRemainder, '=>') + 2);
        }

        $i = $startIndex + 1;
        while ($i < count($lines) && $opens > $closes) {
            $line = $lines[$i];
            $opens += substr_count($line, '[');
            $closes += substr_count($line, ']');

            if (preg_match("/^\s*'([^']+)'\s*=>/", $line, $matches) === 1) {
                $keys[] = $matches[1];
            } elseif (preg_match('/^\s*"([^"]+)"\s*=>/', $line, $matches) === 1) {
                $keys[] = $matches[1];
            }

            $i++;
        }

        return $keys;
    }

    /**
     * @return array<string, list<string>>
     */
    public static function addressConstructionSites(): array
    {
        $sites = [
            'app/Domains/Checkout/Application/Dto/CheckoutAddressInputDto.php',
            'app/Domains/Checkout/Controllers/PlaceOrderRequest.php',
            'app/Domains/Checkout/Services/CheckoutOrderWriter.php',
            'app/Support/Smoke/Performance/PerformanceSmokeSetupFactory.php',
            'app/Support/Smoke/WebhookFlow/WebhookFlowScenario.php',
            'app/Support/Smoke/ApiContract/Scenarios/CheckoutApiContractScenario.php',
        ];

        $cases = [];
        foreach ($sites as $site) {
            $cases[$site] = [$site];
        }

        return $cases;
    }
}
