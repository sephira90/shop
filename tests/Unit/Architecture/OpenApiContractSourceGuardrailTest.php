<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use App\Support\Api\ApiErrorCode;
use cebe\openapi\Reader;
use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Schema;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

final class OpenApiContractSourceGuardrailTest extends TestCase
{
    private const SPEC_PATH = 'docs/api/openapi.yaml';

    private const EXPECTED_PATHS = [
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

    private function loadSpec(): OpenApi
    {
        $path = base_path(self::SPEC_PATH);
        $this->assertFileExists($path, 'docs/api/openapi.yaml must exist as the machine-readable API contract source of truth.');

        $realPath = realpath($path);
        if (! is_string($realPath)) {
            $this->fail(sprintf('OpenAPI spec path did not resolve: %s', $path));
        }

        $openapi = Reader::readFromYamlFile($realPath);
        $this->assertInstanceOf(OpenApi::class, $openapi, 'OpenAPI spec must parse into an OpenApi instance.');

        return $openapi;
    }

    public function test_spec_declares_openapi_3_0(): void
    {
        $openapi = $this->loadSpec();

        $this->assertStringStartsWith('3.0.', $openapi->openapi, 'Spec must declare OpenAPI 3.0.x (3.1 deferred until a stable PHP validator exists; see risk register).');
    }

    public function test_spec_validates_structurally(): void
    {
        $openapi = $this->loadSpec();

        $isValid = $openapi->validate();
        $errors = $openapi->getErrors();
        $realErrors = array_values(array_filter($errors, static fn (string $error): bool => $error !== ''));

        $this->assertTrue($isValid, 'OpenAPI spec must pass cebe structural validation.');
        $this->assertSame([], $realErrors, 'OpenAPI spec must have zero structural errors: '.implode('; ', $realErrors));
    }

    public function test_spec_covers_all_in_scope_paths(): void
    {
        $openapi = $this->loadSpec();

        foreach (self::EXPECTED_PATHS as $path) {
            $this->assertArrayHasKey(
                $path,
                $openapi->paths,
                sprintf('Spec must declare path "%s" — S1 contract coverage.', $path),
            );
        }
    }

    public function test_spec_error_code_enum_matches_php_enum(): void
    {
        $openapi = $this->loadSpec();
        $components = $openapi->components;
        $this->assertNotNull($components, 'Spec must declare a components section.');
        $schema = $components->schemas['ApiErrorCode'] ?? null;

        $this->assertNotNull($schema, 'Spec must declare the ApiErrorCode component schema.');
        $this->assertInstanceOf(Schema::class, $schema, 'ApiErrorCode must be a Schema instance.');

        $phpCodes = array_map(static fn (ApiErrorCode $code): string => $code->value, ApiErrorCode::cases());
        $enum = $schema->enum;

        $this->assertSame(
            $phpCodes,
            $enum,
            'ApiErrorCode literals in spec must match App\\Support\\Api\\ApiErrorCode enum members exactly (no drift).',
        );
    }

    public function test_composer_declares_openapi_validator_dev_dependency(): void
    {
        $contents = (string) File::get(base_path('composer.json'));
        $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

        $this->assertIsArray($decoded, 'composer.json must parse to an array.');
        $this->assertArrayHasKey('require-dev', $decoded, 'composer.json must declare a require-dev section.');
        $requireDev = $decoded['require-dev'];
        $this->assertIsArray($requireDev);

        $hasCebeOpenApi = isset($requireDev['cebe/php-openapi'])
            || isset($requireDev['devizzent/cebe-php-openapi']);

        $this->assertTrue(
            $hasCebeOpenApi,
            'composer.json require-dev must declare a cebe/php-openapi-compatible package for spec parsing/validation (Symfony YAML v8 constraint requires the devizzent fork).',
        );
    }

    public function test_spec_assertion_helper_and_trait_exist(): void
    {
        $helperPath = base_path('tests/Support/OpenApi/SpecAssertionHelper.php');
        $traitPath = base_path('tests/Support/OpenApi/AssertsOpenApiResponse.php');

        $this->assertFileExists($helperPath, 'SpecAssertionHelper must exist for feature-test conformance assertions.');
        $this->assertFileExists($traitPath, 'AssertsOpenApiResponse trait must exist as the convenience entry point.');
    }

    public function test_conformance_feature_test_exists(): void
    {
        $path = base_path('tests/Feature/OpenApiConformanceFeatureTest.php');
        $this->assertFileExists($path, 'OpenApiConformanceFeatureTest must exist as the executable enforcement of the spec contract.');

        $contents = (string) File::get($path);
        $this->assertStringContainsString('assertResponseMatchesOpenApiSpec', $contents, 'Conformance test must invoke the spec assertion helper.');
    }

    public function test_spec_declares_two_distinct_error_envelopes(): void
    {
        $openapi = $this->loadSpec();
        $components = $openapi->components;
        $this->assertNotNull($components, 'Spec must declare a components section.');
        $schemas = $components->schemas;

        $this->assertArrayHasKey('ErrorResponseController', $schemas, 'Spec must model Shape A (controller-caught AuthApplicationException) as ErrorResponseController.');
        $this->assertArrayHasKey('ErrorResponseRenderer', $schemas, 'Spec must model Shape B (renderer-emitted) as ErrorResponseRenderer.');
    }
}
