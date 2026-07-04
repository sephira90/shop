<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use App\Support\Api\ApiExceptionRenderer;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Enforce the R1 single-renderer boundary:
 *
 * - Exactly one invokable renderer class lives under app/Support/Api/.
 * - bootstrap/app.php delegates exception rendering to that class.
 * - No code in app/ computes error.type via class_basename() directly,
 *   so all emitted type/code values flow through the single renderer.
 */
final class ApiExceptionRendererBoundaryTest extends TestCase
{
    public function test_exactly_one_exception_renderer_class_lives_under_support_api(): void
    {
        $matches = [];
        foreach (File::allFiles(app_path('Support/Api')) as $file) {
            $source = File::get($file->getPathname());
            // Heuristic: any class declaring __invoke(Throwable, Request): ?Response
            // signature is treated as an exception-render boundary.
            if (preg_match('/function\s+__invoke\s*\(\s*\\\\?Throwable/i', $source)) {
                $matches[] = $file->getRelativePathname();
            }
        }

        $this->assertSame(
            ['ApiExceptionRenderer.php'],
            $matches,
            'app/Support/Api/ must contain exactly one Throwable-aware invokable renderer.'
        );
    }

    public function test_bootstrap_app_delegates_rendering_to_the_renderer_class(): void
    {
        $bootstrap = File::get(base_path('bootstrap/app.php'));

        $this->assertStringContainsString(
            'use App\Support\Api\ApiExceptionRenderer;',
            $bootstrap,
            'bootstrap/app.php must import the renderer class.'
        );
        $this->assertStringContainsString(
            'app(ApiExceptionRenderer::class)($exception, $request)',
            $bootstrap,
            'bootstrap/app.php must delegate exception rendering to ApiExceptionRenderer resolved via the container.'
        );
        $this->assertStringNotContainsString(
            'ApiResponse::error(',
            $bootstrap,
            'bootstrap/app.php must not bypass the renderer by calling ApiResponse::error() directly.'
        );
    }

    public function test_no_inline_class_basename_calls_for_error_type_outside_the_renderer(): void
    {
        $rendererPath = (new \ReflectionClass(ApiExceptionRenderer::class))->getFileName();
        $this->assertIsString($rendererPath, 'ApiExceptionRenderer must be defined in a file.');

        // Auth controllers are the documented exception: they catch
        // AuthApplicationException to preserve the auth anti-enumeration
        // contract (controlled message shape for known-vs-unknown login) and
        // emit its declared status code. They reuse the same class_basename()
        // pattern as the renderer for the type field, but inline because the
        // auth branch is intentionally not routed through the global renderer.
        $allowlist = [
            'AuthController.php',
            'PasswordController.php',
            'VerificationController.php',
        ];

        $violations = [];

        foreach (File::allFiles(app_path()) as $file) {
            if ($file->getRealPath() === $rendererPath) {
                continue;
            }
            if (in_array($file->getFilename(), $allowlist, true)) {
                continue;
            }

            $source = File::get($file->getPathname());

            if (preg_match('/class_basename\s*\(/', $source)) {
                $relative = str_replace(base_path().DIRECTORY_SEPARATOR, '', $file->getPathname());
                $violations[] = $relative;
            }
        }

        $this->assertSame(
            [],
            $violations,
            'class_basename() for error.type must only be used in ApiExceptionRenderer or the auth anti-enumeration allowlist. Found in: '
            .implode(', ', $violations)
        );
    }
}
