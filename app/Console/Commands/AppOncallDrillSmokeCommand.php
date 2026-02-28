<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\Oncall\OncallDrillRunner;
use Illuminate\Console\Command;

class AppOncallDrillSmokeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:oncall-drill-smoke
        {--with-write-smokes : Include write-path smoke commands (api-contract/webhook-flow)}
        {--persist : Pass --persist to write-path smoke commands}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run on-call drill checks and print escalation guidance.';

    public function __construct(
        private readonly OncallDrillRunner $drillRunner,
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $result = $this->drillRunner->run(
            withWriteSmokes: (bool) $this->option('with-write-smokes'),
            persist: (bool) $this->option('persist'),
        );

        $this->table(
            ['check', 'command', 'status', 'exit_code'],
            array_map(static fn ($check): array => [
                $check->check,
                $check->command,
                $check->status,
                (string) $check->exitCode,
            ], $result->results),
        );

        if ($result->passed()) {
            $this->info('On-call drill passed.');

            return self::SUCCESS;
        }

        $this->table(
            ['check', 'severity', 'owner', 'next_step', 'output_excerpt'],
            array_map(static fn ($failure): array => [
                $failure->check,
                $failure->severity,
                $failure->owner,
                $failure->nextStep,
                $failure->outputExcerpt,
            ], $result->failures),
        );

        $this->error('On-call drill failed. Follow escalation matrix in docs/OPERATIONS_RUNBOOK_CHECKOUT_WEBHOOKS.md.');

        return self::FAILURE;
    }
}
