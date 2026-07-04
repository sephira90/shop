<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

final class ApiControllerDomainExceptionBoundaryTest extends TestCase
{
    /**
     * Ensure API V1 controllers do not inline DomainException catch blocks.
     */
    public function test_api_v1_controllers_do_not_catch_domain_exception_directly(): void
    {
        $controllerDirectory = app_path('Http/Controllers/Api/V1');
        $checkedFiles = [];

        foreach (File::allFiles($controllerDirectory) as $file) {
            $checkedFiles[] = $file->getFilename();
            $source = File::get($file->getPathname());

            $this->assertStringNotContainsString(
                'catch (DomainException',
                $source,
                "{$file->getFilename()} must rely on the global API exception renderer for DomainException."
            );
            $this->assertStringNotContainsString(
                'use DomainException;',
                $source,
                "{$file->getFilename()} must not import DomainException for transport-local handling."
            );
        }

        $this->assertNotEmpty($checkedFiles, 'No API V1 controller files found for DomainException guardrail.');
    }

    /**
     * Ensure API V1 controllers emit error envelopes only through the global
     * renderer, not by calling ApiResponse::error() inline. The documented
     * exceptions are controllers that must catch an application/auth-specific
     * exception and emit its declared status code:
     *
     *   - AuthController / PasswordController / VerificationController: catch
     *     AuthApplicationException to preserve the auth anti-enumeration
     *     contract (controlled envelope shape for known-vs-unknown login).
     *   - PaymentWebhookController / ShippingWebhookController: catch
     *     WebhookIngressException to surface its declared HTTP status.
     *
     * Any other ApiResponse::error() call in a controller is a regression of
     * the R1 single-renderer boundary.
     */
    public function test_api_v1_controllers_emit_errors_only_through_the_renderer_except_documented_allowlist(): void
    {
        $controllerDirectory = app_path('Http/Controllers/Api/V1');

        $allowlist = [
            'AuthController.php',
            'PasswordController.php',
            'VerificationController.php',
            'PaymentWebhookController.php',
            'ShippingWebhookController.php',
        ];

        $violations = [];

        foreach (File::allFiles($controllerDirectory) as $file) {
            if (in_array($file->getFilename(), $allowlist, true)) {
                continue;
            }

            $source = File::get($file->getPathname());

            if (str_contains($source, 'ApiResponse::error(')) {
                $relative = str_replace(base_path().DIRECTORY_SEPARATOR, '', $file->getPathname());
                $violations[] = $relative;
            }
        }

        $this->assertSame(
            [],
            $violations,
            'API V1 controllers must throw exceptions routed through ApiExceptionRenderer instead of calling '
            .'ApiResponse::error() directly. Found inline calls in: '.implode(', ', $violations)
        );
    }
}
