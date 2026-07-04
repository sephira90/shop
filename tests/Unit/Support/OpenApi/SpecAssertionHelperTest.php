<?php

declare(strict_types=1);

namespace Tests\Unit\Support\OpenApi;

use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Schema;
use Tests\Support\OpenApi\SpecAssertionHelper;
use Tests\TestCase;

final class SpecAssertionHelperTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        SpecAssertionHelper::resetCachedSpec();
    }

    public function test_spec_loads_and_validates_structurally(): void
    {
        $spec = SpecAssertionHelper::spec();

        $this->assertInstanceOf(OpenApi::class, $spec);
        $this->assertSame('3.0.3', $spec->openapi);
    }

    public function test_spec_covers_in_scope_paths(): void
    {
        $spec = SpecAssertionHelper::spec();

        $expected = [
            '/auth/register',
            '/auth/login',
            '/auth/logout',
            '/auth/me',
            '/auth/profile',
            '/auth/forgot-password',
            '/auth/reset-password',
            '/auth/email/verify/{id}/{hash}',
            '/auth/email/verification-notification',
            '/catalog/products',
            '/catalog/products/{slug}',
            '/catalog/categories',
            '/cart',
            '/cart/items',
            '/cart/items/{variantId}',
        ];

        foreach ($expected as $path) {
            $this->assertArrayHasKey(
                $path,
                $spec->paths,
                sprintf('OpenAPI spec must declare path "%s".', $path),
            );
        }
    }

    public function test_spec_caches_loaded_instance(): void
    {
        $first = SpecAssertionHelper::spec();
        $second = SpecAssertionHelper::spec();

        $this->assertSame($first, $second, 'SpecAssertionHelper should cache the parsed spec for the duration of a test run.');
    }

    public function test_error_code_enum_in_spec_matches_php_enum(): void
    {
        $spec = SpecAssertionHelper::spec();
        $components = $spec->components;
        $this->assertNotNull($components);
        $schema = $components->schemas['ApiErrorCode'] ?? null;

        $this->assertInstanceOf(Schema::class, $schema, 'Spec must declare the ApiErrorCode component schema.');

        $enum = $schema->enum;

        $this->assertSame(
            [
                'validation_failed',
                'unauthenticated',
                'forbidden',
                'not_found',
                'state_transition_not_allowed',
                'stale_aggregate',
                'webhook_ingress_rejected',
                'domain_failure',
                'internal_error',
            ],
            $enum,
            'ApiErrorCode enum in spec must match App\Support\Api\ApiErrorCode literals exactly.',
        );
    }
}
