<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class RepositoryReadBoundaryTest extends TestCase
{
    public function test_legacy_mixed_context_repository_files_are_not_present(): void
    {
        $this->assertFileDoesNotExist(
            app_path('Repositories/OrderRepository.php'),
            'Mixed-context OrderRepository must not be reintroduced.'
        );

        $this->assertFileDoesNotExist(
            app_path('Repositories/ProductRepository.php'),
            'Mixed-context ProductRepository must not be reintroduced.'
        );
    }

    public function test_read_repositories_do_not_reference_other_context_dtos(): void
    {
        $expectations = [
            [
                'path' => app_path('Repositories/AccountOrderReadRepository.php'),
                'forbidden' => [
                    'App\\Application\\Admin\\Orders\\Dto\\',
                ],
            ],
            [
                'path' => app_path('Repositories/AdminOrderReadRepository.php'),
                'forbidden' => [
                    'App\\Application\\Account\\Orders\\Dto\\',
                ],
            ],
            [
                'path' => app_path('Repositories/AdminProductReadRepository.php'),
                'forbidden' => [
                    'App\\Domains\\Catalog\\Application\\Dto\\',
                    'App\\Domains\\Catalog\\Contracts\\Dto\\',
                ],
            ],
        ];

        foreach ($expectations as $expectation) {
            $contents = File::get($expectation['path']);

            foreach ($expectation['forbidden'] as $forbiddenNamespace) {
                $this->assertStringNotContainsString(
                    $forbiddenNamespace,
                    $contents,
                    basename($expectation['path'])." must not depend on {$forbiddenNamespace}."
                );
            }
        }
    }
}
