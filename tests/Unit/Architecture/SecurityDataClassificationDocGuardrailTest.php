<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

final class SecurityDataClassificationDocGuardrailTest extends TestCase
{
    private const DOC_PATH = 'docs/SECURITY_DATA_CLASSIFICATION.md';

    public function test_security_data_classification_doc_exists(): void
    {
        $this->assertFileExists(base_path(self::DOC_PATH));
    }

    public function test_security_data_classification_doc_names_pii_columns(): void
    {
        $contents = (string) File::get(base_path(self::DOC_PATH));

        $requiredColumns = [
            '`users` | `email`',
            '`users` | `phone`',
            '`users` | `password`',
            '`orders` | `email`',
            '`orders` | `billing_address`',
            '`orders` | `shipping_address`',
            '`payments` | `payload`',
            '`shipments` | `payload`',
        ];

        foreach ($requiredColumns as $row) {
            $this->assertStringContainsString(
                $row,
                $contents,
                'Classification doc must name PII column row: '.$row,
            );
        }
    }

    public function test_security_data_classification_doc_locks_allowed_address_keys(): void
    {
        $contents = (string) File::get(base_path(self::DOC_PATH));

        foreach (['line1', 'city', 'country', 'postcode'] as $key) {
            $this->assertStringContainsString(
                $key,
                $contents,
                'Classification doc must reference address allowlist key '.$key.'.',
            );
        }

        $rejected = ['phone', 'email', 'notes', 'recipient_name', 'card_number', 'cvv'];
        foreach ($rejected as $key) {
            $this->assertStringContainsString(
                $key,
                $contents,
                'Classification doc must reference rejected address key '.$key.' in the rejection list.',
            );
        }
    }

    public function test_security_data_classification_doc_references_encryption_followup(): void
    {
        $contents = (string) File::get(base_path(self::DOC_PATH));

        $this->assertStringContainsString('field-level encryption', $contents);
        $this->assertStringContainsString('APP_ENCRYPTION_KEY', $contents);
        $this->assertStringContainsString('backfill', $contents);
    }

    public function test_security_data_classification_doc_references_enforcing_guardrails(): void
    {
        $contents = (string) File::get(base_path(self::DOC_PATH));

        $this->assertStringContainsString(
            'AddressPayloadBoundaryGuardrailTest.php',
            $contents,
            'Classification doc must reference the address boundary guardrail.',
        );
        $this->assertStringContainsString(
            'GatewayPayloadBoundaryGuardrailTest.php',
            $contents,
            'Classification doc must reference the gateway payload boundary guardrail.',
        );
    }
}
