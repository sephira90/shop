<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use Illuminate\Support\Facades\File;
use SplFileInfo;
use Tests\TestCase;

final class ApiControllerValidationBoundaryTest extends TestCase
{
    /**
     * Ensure API V1 controllers keep validation in dedicated FormRequest classes.
     */
    public function test_api_v1_controllers_do_not_use_inline_request_validate_calls(): void
    {
        $controllerDirectory = app_path('Http/Controllers/Api/V1');
        $checkedFiles = [];

        /** @var SplFileInfo $file */
        foreach (File::allFiles($controllerDirectory) as $file) {
            $checkedFiles[] = $file->getFilename();

            $this->assertStringNotContainsString(
                '->validate(',
                File::get($file->getPathname()),
                "{$file->getFilename()} must keep validation in FormRequest classes."
            );
        }

        $this->assertNotEmpty($checkedFiles, 'No API V1 controller files found for validation guardrail.');
    }
}
