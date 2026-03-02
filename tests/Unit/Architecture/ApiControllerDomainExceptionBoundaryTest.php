<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use Illuminate\Support\Facades\File;
use SplFileInfo;
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

        /** @var SplFileInfo $file */
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
}
