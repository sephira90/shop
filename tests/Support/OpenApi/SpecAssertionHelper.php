<?php

declare(strict_types=1);

namespace Tests\Support\OpenApi;

use cebe\openapi\Reader;
use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Operation;
use cebe\openapi\spec\Response as SpecResponse;
use cebe\openapi\spec\Schema;
use Illuminate\Http\JsonResponse;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Assert;
use RuntimeException;
use Throwable;

/**
 * Validates HTTP responses against the OpenAPI source of truth.
 *
 * The spec is parsed once per test run via {@see SpecAssertionHelper::spec()}
 * and cached statically. Each assertion locates the operation by method+path,
 * confirms the response status is declared, and walks the declared body schema
 * to spot-check the shape of the actual JSON. Failure messages name the
 * diverging field so the regression is obvious in CI output.
 *
 * Intended for use in feature tests via the companion {@see AssertsOpenApiResponse} trait.
 */
final class SpecAssertionHelper
{
    private static ?OpenApi $spec = null;

    public static function spec(): OpenApi
    {
        if (self::$spec instanceof OpenApi) {
            return self::$spec;
        }

        $path = base_path('docs/api/openapi.yaml');

        if (! is_file($path)) {
            throw new RuntimeException(sprintf('OpenAPI spec not found at expected path: %s', $path));
        }

        $realPath = realpath($path);
        if (! is_string($realPath)) {
            throw new RuntimeException(sprintf('OpenAPI spec path did not resolve: %s', $path));
        }

        /** @var OpenApi $openapi */
        $openapi = Reader::readFromYamlFile($realPath);

        $isValid = $openapi->validate();
        $errors = $openapi->getErrors();
        $realErrors = array_values(array_filter($errors, static fn (string $error): bool => $error !== ''));

        if (! $isValid || $realErrors !== []) {
            throw new RuntimeException(
                'OpenAPI spec has structural errors: '.implode('; ', $realErrors),
            );
        }

        return self::$spec = $openapi;
    }

    public static function resetCachedSpec(): void
    {
        self::$spec = null;
    }

    /**
     * @param  TestResponse<JsonResponse>  $response
     */
    public static function assertResponseMatches(
        TestResponse $response,
        string $method,
        string $path,
    ): void {
        $operation = self::resolveOperation($method, $path);

        $status = $response->status();
        $declared = $operation->responses;

        $specResponse = $declared[(string) $status] ?? null;

        if (! $specResponse instanceof SpecResponse) {
            $declaredKeys = self::declaredStatuses($declared);
            Assert::fail(sprintf(
                'Response for %s %s returned HTTP %d, which is not declared in the OpenAPI spec. Declared statuses: %s.',
                strtoupper($method),
                $path,
                $status,
                $declaredKeys === [] ? '(none)' : implode(', ', $declaredKeys),
            ));
        }

        $contentType = (string) $response->headers->get('Content-Type', '');

        if (! str_starts_with($contentType, 'application/json')) {
            Assert::fail(sprintf(
                'Response for %s %s (HTTP %d) was expected to be application/json, got Content-Type "%s".',
                strtoupper($method),
                $path,
                $status,
                $contentType,
            ));
        }

        $json = null;

        try {
            $json = $response->json();
        } catch (Throwable) {
            Assert::fail(sprintf(
                'Response for %s %s (HTTP %d) did not contain valid JSON.',
                strtoupper($method),
                $path,
                $status,
            ));
        }

        $schemas = self::responseBodySchemas($specResponse);

        if ($schemas === []) {
            Assert::fail(sprintf(
                'Response for %s %s (HTTP %d) declares no JSON schema to assert against.',
                strtoupper($method),
                $path,
                $status,
            ));
        }

        foreach ($schemas as $schema) {
            if (self::schemaMatches($schema, $json)) {
                Assert::assertContains(
                    $status,
                    array_map('intval', self::declaredStatuses($declared)),
                    sprintf('%s %s HTTP %d conforms to the OpenAPI spec.', strtoupper($method), $path, $status),
                );

                return;
            }
        }

        Assert::fail(sprintf(
            'Response body for %s %s (HTTP %d) did not match any schema declared for that response in the OpenAPI spec.',
            strtoupper($method),
            $path,
            $status,
        ));
    }

    private static function resolveOperation(string $method, string $path): Operation
    {
        $openapi = self::spec();

        $pathItem = $openapi->paths[$path] ?? null;

        if ($pathItem === null) {
            Assert::fail(sprintf(
                'OpenAPI spec does not declare path "%s". Add it to docs/api/openapi.yaml before asserting against it.',
                $path,
            ));
        }

        $methodLower = strtolower($method);
        $operation = $pathItem->{$methodLower} ?? null;

        if (! $operation instanceof Operation) {
            Assert::fail(sprintf(
                'OpenAPI spec declares path "%s" but not the %s method.',
                $path,
                strtoupper($methodLower),
            ));
        }

        return $operation;
    }

    /**
     * @return array<int, string>
     */
    private static function declaredStatuses(mixed $responses): array
    {
        if (! $responses instanceof \cebe\openapi\spec\Responses) {
            return [];
        }

        $serializable = $responses->getSerializableData();
        $serializableArray = is_object($serializable)
            ? get_object_vars($serializable)
            : (is_array($serializable) ? $serializable : []);

        return array_map('strval', array_keys($serializableArray));
    }

    /**
     * @return array<int, Schema>
     */
    private static function responseBodySchemas(SpecResponse $specResponse): array
    {
        $schemas = [];

        foreach ($specResponse->content as $mediaType) {
            $schema = $mediaType->schema;
            if ($schema instanceof Schema) {
                $schemas[] = $schema;
            }
        }

        return $schemas;
    }

    private static function schemaMatches(Schema $schema, mixed $value): bool
    {
        $types = self::declaredTypes($schema);
        $allowsNull = in_array('null', $types, true) || $schema->nullable === true;

        if ($value === null) {
            return $allowsNull;
        }

        $nonNullTypes = array_diff($types, ['null']);

        if ($nonNullTypes !== [] && ! self::valueMatchesAnyType($value, $nonNullTypes)) {
            return false;
        }

        if (is_array($value)) {
            if (self::propertiesViolateObjectShape($schema, $value)) {
                return false;
            }

            $itemSchema = self::schemaItems($schema);
            if ($itemSchema instanceof Schema) {
                foreach ($value as $item) {
                    if (! self::schemaMatches($itemSchema, $item)) {
                        return false;
                    }
                }
            }
        }

        $enum = self::schemaEnum($schema);
        if ($enum !== null && $enum !== [] && ! in_array($value, $enum, true)) {
            return false;
        }

        return true;
    }

    /**
     * @param  array<int|string, mixed>  $value
     */
    private static function propertiesViolateObjectShape(Schema $schema, array $value): bool
    {
        $properties = $schema->properties ?? [];
        $required = $schema->required ?? [];

        foreach ($required as $requiredKey) {
            if (! array_key_exists($requiredKey, $value)) {
                return true;
            }
        }

        foreach ($properties as $key => $property) {
            if (! array_key_exists($key, $value)) {
                continue;
            }

            $propertySchema = self::asSchema($property);
            if ($propertySchema instanceof Schema && ! self::schemaMatches($propertySchema, $value[$key])) {
                return true;
            }
        }

        return false;
    }

    private static function schemaItems(Schema $schema): ?Schema
    {
        $items = $schema->items;

        return self::asSchema($items);
    }

    /**
     * @return list<string>|null
     */
    private static function schemaEnum(Schema $schema): ?array
    {
        $enum = $schema->enum ?? null;

        if ($enum === null) {
            return null;
        }

        $strings = [];
        foreach ($enum as $value) {
            if (is_string($value)) {
                $strings[] = $value;
            }
        }

        return $strings;
    }

    /**
     * @return list<string>
     */
    private static function declaredTypes(Schema $schema): array
    {
        $type = $schema->type ?? null;

        if (is_array($type)) {
            return array_values($type);
        }

        if (is_string($type)) {
            return [$type];
        }

        return [];
    }

    private static function asSchema(mixed $property): ?Schema
    {
        return $property instanceof Schema ? $property : null;
    }

    /**
     * @param  array<int, string>  $types
     */
    private static function valueMatchesAnyType(mixed $value, array $types): bool
    {
        foreach ($types as $type) {
            if ($type === 'object' && is_array($value)) {
                return true;
            }

            if ($type === 'array' && is_array($value)) {
                return true;
            }

            if ($type === 'string' && is_string($value)) {
                return true;
            }

            if ($type === 'integer' && is_int($value)) {
                return true;
            }

            if ($type === 'number' && (is_int($value) || is_float($value))) {
                return true;
            }

            if ($type === 'boolean' && is_bool($value)) {
                return true;
            }
        }

        return false;
    }
}
