<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use Tests\TestCase;

class OperationalCommandBoundaryTest extends TestCase
{
    /**
     * Ensure operational commands depend only on explicit support-layer boundaries.
     */
    public function test_operational_commands_depend_on_support_boundaries_only(): void
    {
        foreach ($this->operationalCommandDependencyPrefixes() as $commandClass => $allowedPrefixes) {
            $reflection = new ReflectionClass($commandClass);
            $constructor = $reflection->getConstructor();

            $this->assertNotNull($constructor, "{$commandClass} must define constructor dependencies.");

            foreach ($constructor->getParameters() as $parameter) {
                $type = $parameter->getType();

                $this->assertInstanceOf(
                    ReflectionNamedType::class,
                    $type,
                    "{$commandClass} constructor parameter {$parameter->getName()} must have a named type."
                );
                $this->assertFalse(
                    $type->isBuiltin(),
                    "{$commandClass} constructor parameter {$parameter->getName()} must be a class type."
                );

                $dependency = $type->getName();

                $this->assertTrue(
                    $this->startsWithAny($dependency, $allowedPrefixes),
                    "{$commandClass} must depend on explicit support boundaries only; got {$dependency}."
                );
            }
        }
    }

    /**
     * Ensure operational commands stay orchestration-only and keep helper logic in support services.
     */
    public function test_operational_commands_do_not_keep_local_helper_methods(): void
    {
        foreach (array_keys($this->operationalCommandDependencyPrefixes()) as $commandClass) {
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
                "{$commandClass} must remain orchestration-only; helper methods belong in support boundaries."
            );
        }
    }

    /**
     * @return array<class-string,list<string>>
     */
    private function operationalCommandDependencyPrefixes(): array
    {
        return [
            'App\\Console\\Commands\\AppMaintenanceCleanupCommand' => [
                'App\\Support\\Maintenance\\',
            ],
            'App\\Console\\Commands\\AppOncallDrillSmokeCommand' => [
                'App\\Support\\Oncall\\',
            ],
            'App\\Console\\Commands\\AppObservabilityAlertCheckCommand' => [
                'App\\Support\\Observability\\',
            ],
            'App\\Console\\Commands\\AppObservabilityReportCommand' => [
                'App\\Support\\Observability\\',
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
