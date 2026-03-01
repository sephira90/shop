<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use Illuminate\Support\Facades\File;
use SplFileInfo;
use Tests\TestCase;

class RepositoryStatusInterpretationGuardrailTest extends TestCase
{
    public function test_repositories_do_not_embed_account_order_summary_status_interpretation(): void
    {
        $forbiddenPatterns = [
            'OrderStatus::PAID',
            'PaymentStatus::CAPTURED',
            'ShipmentStatus::PACKED',
            'ShipmentStatus::SHIPPED',
        ];

        /** @var SplFileInfo $file */
        foreach (File::allFiles(app_path('Repositories')) as $file) {
            $contents = File::get($file->getPathname());

            foreach ($forbiddenPatterns as $pattern) {
                $this->assertStringNotContainsString(
                    $pattern,
                    $contents,
                    "{$file->getFilename()} must keep derived account-order summary semantics outside repository layer."
                );
            }
        }
    }
}
