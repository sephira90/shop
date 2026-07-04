<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use App\Support\Api\ApiErrorCode;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Enforce the R1 additive error.code contract: every literal string that
 * matches a closed ApiErrorCode value MUST come from the enum itself, never
 * from an inline string. This keeps the public literal set stable and
 * discoverable, preventing drift between emitters and consumers.
 */
final class ApiErrorCodeStabilityGuardrailTest extends TestCase
{
    public function test_no_inline_error_code_string_literals_in_app_except_the_enum_definition(): void
    {
        $closedLiterals = array_map(
            static fn (ApiErrorCode $code): string => $code->value,
            ApiErrorCode::cases(),
        );

        $enumFile = (new \ReflectionClass(ApiErrorCode::class))->getFileName();
        $this->assertIsString($enumFile, 'ApiErrorCode must be defined in a file.');

        $violations = [];
        $scanned = 0;

        foreach (File::allFiles(app_path()) as $file) {
            $realPath = $file->getRealPath();
            if ($realPath === $enumFile) {
                continue;
            }

            $scanned++;
            $source = File::get($file->getPathname());

            foreach ($closedLiterals as $literal) {
                // Match the literal as a single-quoted PHP string so incidental
                // substrings inside comments/logs do not trigger false positives.
                $needle = "'{$literal}'";
                if (str_contains($source, $needle)) {
                    $relative = str_replace(base_path().DIRECTORY_SEPARATOR, '', $file->getPathname());
                    $violations[] = "{$relative}: inline literal {$needle}";
                }
            }
        }

        $this->assertNotSame(0, $scanned, 'No app/ files scanned; guardrail is misconfigured.');
        $this->assertSame(
            [],
            $violations,
            'Inline ApiErrorCode literals must come from the enum. Found: '.implode(', ', $violations)
        );
    }

    public function test_api_error_code_enum_exposes_the_closed_literal_set_locked_by_r1(): void
    {
        $expected = [
            'validation_failed',
            'unauthenticated',
            'forbidden',
            'not_found',
            'state_transition_not_allowed',
            'stale_aggregate',
            'webhook_ingress_rejected',
            'domain_failure',
            'internal_error',
        ];

        $actual = array_map(
            static fn (ApiErrorCode $code): string => $code->value,
            ApiErrorCode::cases(),
        );

        $this->assertSame($expected, $actual, 'ApiErrorCode literal set drifted from the R1 contract.');
    }
}
