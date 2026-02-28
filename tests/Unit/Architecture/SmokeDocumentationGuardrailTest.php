<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class SmokeDocumentationGuardrailTest extends TestCase
{
    public function test_readme_and_runbook_document_targeted_smoke_execution_modes(): void
    {
        $readme = File::get(base_path('README.md'));
        $runbook = File::get(base_path('docs/OPERATIONS_RUNBOOK_CHECKOUT_WEBHOOKS.md'));

        $this->assertStringContainsString('app:api-contract-smoke --only=shipping_webhook', $readme);
        $this->assertStringContainsString('app:performance-smoke --only=admin_orders_summary', $readme);
        $this->assertStringContainsString('app:webhook-flow-smoke --persist', $readme);

        $this->assertStringContainsString('app:api-contract-smoke --only=payment_webhook', $runbook);
        $this->assertStringContainsString('app:performance-smoke --only=checkout_place_order', $runbook);
        $this->assertStringContainsString('app:webhook-flow-smoke --persist', $runbook);
    }
}
