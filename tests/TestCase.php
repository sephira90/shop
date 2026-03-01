<?php

declare(strict_types=1);

namespace Tests;

use App\Support\Data\TypedValue;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Testing\PendingCommand;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\Response;

abstract class TestCase extends BaseTestCase
{
    /**
     * Return a typed PendingCommand for fluent console assertions.
     *
     * @param  array<string, mixed>  $parameters
     */
    protected function artisanCommand(string $command, array $parameters = []): PendingCommand
    {
        $result = $this->artisan($command, $parameters);

        if (! $result instanceof PendingCommand) {
            $this->fail(sprintf('Expected PendingCommand for [%s].', $command));
        }

        return $result;
    }

    /**
     * @param  TestResponse<Response>  $response
     */
    protected function jsonString(TestResponse $response, string $path): string
    {
        return TypedValue::string($response->json($path));
    }

    /**
     * @param  TestResponse<Response>  $response
     */
    protected function jsonInt(TestResponse $response, string $path): int
    {
        return TypedValue::int($response->json($path));
    }

    /**
     * @param  TestResponse<Response>  $response
     */
    protected function jsonFloat(TestResponse $response, string $path): float
    {
        return TypedValue::float($response->json($path));
    }

    /**
     * @param  TestResponse<Response>  $response
     * @return array<string, mixed>
     */
    protected function jsonArray(TestResponse $response, string $path): array
    {
        return TypedValue::associativeArray($response->json($path));
    }

    /**
     * @param  TestResponse<Response>  $response
     * @return list<array<string, mixed>>
     */
    protected function jsonArrayList(TestResponse $response, string $path): array
    {
        return TypedValue::listOfAssociativeArrays($response->json($path));
    }
}
