<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class DockerComposeContractGuardrailTest extends TestCase
{
    public function test_root_docker_compose_exposes_expected_local_stack_contract(): void
    {
        $rootCompose = File::get(base_path('docker-compose.yml'));

        $this->assertComposeHasSharedStackContract($rootCompose);
        $this->assertStringContainsString('context: .', $rootCompose);
        $this->assertStringContainsString('./docker/nginx.conf:/etc/nginx/conf.d/default.conf', $rootCompose);
    }

    public function test_legacy_docker_compose_file_keeps_shared_stack_contract(): void
    {
        $legacyCompose = File::get(base_path('docker/compose.yml'));

        $this->assertComposeHasSharedStackContract($legacyCompose);
        $this->assertStringContainsString('context: ..', $legacyCompose);
        $this->assertStringContainsString('./nginx.conf:/etc/nginx/conf.d/default.conf', $legacyCompose);
    }

    private function assertComposeHasSharedStackContract(string $composeFile): void
    {
        $expectedSnippets = [
            'app:',
            'nginx:',
            'db:',
            'redis:',
            'image: mysql:8.4',
            'image: redis:7-alpine',
            'image: nginx:1.27-alpine',
            'DB_CONNECTION: mysql',
            'REDIS_HOST: redis',
            'MYSQL_DATABASE: shop',
            'MYSQL_USER: shop',
            'MYSQL_PASSWORD: shop',
        ];

        foreach ($expectedSnippets as $snippet) {
            $this->assertStringContainsString(
                $snippet,
                $composeFile,
                sprintf('Docker compose contract must contain [%s].', $snippet),
            );
        }
    }
}
