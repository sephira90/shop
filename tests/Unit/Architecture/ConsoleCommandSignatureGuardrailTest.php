<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use App\Support\Observability\ObservabilityReportCommandInvocationFactory;
use App\Support\Oncall\OncallDrillCheckPlanFactory;
use App\Support\Operations\Dto\ConsoleCommandInvocationDto;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ConsoleCommandSignatureGuardrailTest extends TestCase
{
    public function test_critical_operational_and_smoke_commands_expose_expected_options(): void
    {
        foreach ($this->expectedCommandOptions() as $commandName => $options) {
            $command = $this->resolveCommand($commandName);
            $definition = $command->getDefinition();

            foreach ($options as $option) {
                $this->assertTrue(
                    $definition->hasOption($option),
                    sprintf('Command [%s] must expose option [--%s].', $commandName, $option),
                );
            }
        }
    }

    public function test_nested_command_invocations_use_existing_command_options_only(): void
    {
        $invocations = [
            app(ObservabilityReportCommandInvocationFactory::class)->makeFromAlertConfig(),
        ];

        foreach (app(OncallDrillCheckPlanFactory::class)->build(true, true) as $check) {
            $invocations[] = new ConsoleCommandInvocationDto(
                command: $check->command,
                parameters: $check->parameters,
            );
        }

        foreach ($invocations as $invocation) {
            $command = $this->resolveCommand($invocation->command);
            $definition = $command->getDefinition();

            foreach (array_keys($invocation->parameters) as $parameter) {
                $option = ltrim($parameter, '-');

                $this->assertTrue(
                    $definition->hasOption($option),
                    sprintf(
                        'Nested invocation [%s] must use defined option [%s].',
                        $invocation->command,
                        $parameter,
                    ),
                );
            }
        }
    }

    /**
     * @return array<string,list<string>>
     */
    private function expectedCommandOptions(): array
    {
        return [
            'app:maintenance-cleanup' => [
                'dry-run',
                'idempotency-retain-hours',
                'webhook-retain-hours',
                'active-cart-retain-hours',
                'inactive-cart-retain-hours',
            ],
            'app:oncall-drill-smoke' => [
                'with-write-smokes',
                'persist',
            ],
            'app:observability-alert-check' => [],
            'app:observability-report' => [
                'minutes',
                'source',
                'max-api-slow-rate',
                'max-webhook-lag-warn-rate',
                'require-api-samples',
                'require-webhook-samples',
                'json',
            ],
            'app:api-contract-smoke' => [
                'persist',
                'only',
            ],
            'app:webhook-flow-smoke' => [
                'persist',
            ],
            'app:performance-smoke' => [
                'max-catalog-ms',
                'max-catalog-queries',
                'max-catalog-warm-ms',
                'max-catalog-warm-queries',
                'max-cart-ms',
                'max-cart-queries',
                'max-checkout-ms',
                'max-checkout-queries',
                'max-orders-ms',
                'max-orders-queries',
                'max-admin-products-ms',
                'max-admin-products-queries',
                'persist',
                'only',
            ],
        ];
    }

    private function resolveCommand(string $commandName): Command
    {
        $command = Artisan::all()[$commandName] ?? null;

        $this->assertInstanceOf(Command::class, $command, sprintf('Command [%s] must be registered.', $commandName));

        return $command;
    }
}
