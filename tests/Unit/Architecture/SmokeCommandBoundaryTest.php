<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use Tests\TestCase;

class SmokeCommandBoundaryTest extends TestCase
{
    public function test_smoke_commands_depend_on_support_smoke_boundaries_only(): void
    {
        foreach ($this->commandDependencyPrefixes() as $commandClass => $allowedPrefixes) {
            $reflection = new ReflectionClass($commandClass);
            $constructor = $reflection->getConstructor();

            $this->assertNotNull($constructor, "{$commandClass} must define constructor dependencies.");

            foreach ($constructor->getParameters() as $parameter) {
                $type = $parameter->getType();

                $this->assertInstanceOf(
                    ReflectionNamedType::class,
                    $type,
                    "{$commandClass} constructor parameter {$parameter->getName()} must have a named type.",
                );
                $this->assertFalse(
                    $type->isBuiltin(),
                    "{$commandClass} constructor parameter {$parameter->getName()} must be a class type.",
                );

                $dependency = $type->getName();

                $this->assertTrue(
                    $this->startsWithAny($dependency, $allowedPrefixes),
                    "{$commandClass} must depend on support smoke boundaries only; got {$dependency}.",
                );
            }
        }
    }

    public function test_smoke_commands_remain_orchestration_only(): void
    {
        foreach (array_keys($this->commandDependencyPrefixes()) as $commandClass) {
            $reflection = new ReflectionClass($commandClass);

            $extraMethods = array_values(array_map(
                static fn (ReflectionMethod $method): string => $method->getName(),
                array_filter(
                    $reflection->getMethods(),
                    static fn (ReflectionMethod $method): bool => $method->class === $commandClass
                        && ! in_array($method->getName(), ['__construct', 'handle'], true),
                ),
            ));

            $this->assertSame(
                [],
                $extraMethods,
                "{$commandClass} must remain orchestration-only; helper logic belongs in support smoke services.",
            );
        }
    }

    /**
     * @return array<class-string,list<string>>
     */
    private function commandDependencyPrefixes(): array
    {
        return [
            'App\\Console\\Commands\\AppApiContractSmokeCommand' => [
                'App\\Support\\Smoke\\',
                'App\\Support\\Smoke\\ApiContract\\',
            ],
            'App\\Console\\Commands\\AppWebhookFlowSmokeCommand' => [
                'App\\Support\\Smoke\\',
                'App\\Support\\Smoke\\WebhookFlow\\',
            ],
            'App\\Console\\Commands\\AppPerformanceSmokeCommand' => [
                'App\\Support\\Smoke\\',
                'App\\Support\\Smoke\\Performance\\',
            ],
        ];
    }

    /**
     * @param  list<string>  $prefixes
     */
    private function startsWithAny(string $value, array $prefixes): bool
    {
        foreach ($prefixes as $prefix) {
            if (str_starts_with($value, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
