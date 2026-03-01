<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use Illuminate\Support\Facades\File;
use SplFileInfo;
use Tests\TestCase;

class ApiControllerAuthenticatedUserBoundaryTest extends TestCase
{
    public function test_api_v1_controllers_do_not_inline_authenticated_user_resolution(): void
    {
        foreach ($this->controllerFiles() as $file) {
            $contents = File::get($file->getPathname());
            $path = $file->getPathname();

            $this->assertSame(
                0,
                preg_match('/\$\w+->user\s*\(/', $contents),
                "{$path} should use the shared authenticated-user concern instead of raw request->user()."
            );
            $this->assertStringNotContainsString(
                "Auth::guard('sanctum')->user()",
                $contents,
                "{$path} should not resolve Sanctum users inline."
            );
            $this->assertStringNotContainsString(
                "ApiResponse::error('Authentication is required.', Response::HTTP_UNAUTHORIZED)",
                $contents,
                "{$path} should centralize unauthenticated API responses via the shared concern."
            );
        }
    }

    /**
     * @return list<SplFileInfo>
     */
    private function controllerFiles(): array
    {
        $controllerDirectory = app_path('Http/Controllers/Api/V1');

        return array_values(File::allFiles($controllerDirectory));
    }
}
