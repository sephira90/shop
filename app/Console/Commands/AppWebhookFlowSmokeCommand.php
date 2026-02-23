<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\Smoke\SmokePersistenceGuard;
use App\Support\Smoke\WebhookFlow\WebhookFlowScenario;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Throwable;

class AppWebhookFlowSmokeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:webhook-flow-smoke
        {--persist : Persist smoke records instead of rolling them back in production.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run checkout and webhook integration smoke checks with idempotency guards.';

    /**
     * Create command instance.
     */
    public function __construct(
        private readonly WebhookFlowScenario $webhookFlowScenario,
        private readonly SmokePersistenceGuard $persistenceGuard,
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $originalQueueConnection = Config::get('queue.default');
        Config::set('queue.default', 'sync');

        try {
            $execution = $this->persistenceGuard->run(
                $this->shouldRollbackSmokeData(),
                fn (): array => $this->webhookFlowScenario->run(),
            );
        } catch (Throwable $exception) {
            $this->error('Webhook flow smoke failed: '.$exception->getMessage());

            return self::FAILURE;
        } finally {
            Config::set('queue.default', $originalQueueConnection);
        }

        /** @var array{
         *     order_id:string,
         *     payment_id:int,
         *     shipment_id:int,
         *     order_status:string,
         *     payment_status:string,
         *     shipment_status:string
         * } $result
         */
        $result = $execution['result'];

        $this->table(
            ['metric', 'value'],
            [
                ['order_id', (string) $result['order_id']],
                ['payment_id', (string) $result['payment_id']],
                ['shipment_id', (string) $result['shipment_id']],
                ['order_status', $result['order_status']],
                ['payment_status', $result['payment_status']],
                ['shipment_status', $result['shipment_status']],
            ],
        );

        if ($execution['rolled_back']) {
            $this->warn('Production safeguard: smoke data rolled back. Use --persist to keep records.');
        }

        $this->info('Webhook flow smoke checks passed.');

        return self::SUCCESS;
    }

    /**
     * Determine whether smoke writes must be rolled back.
     */
    private function shouldRollbackSmokeData(): bool
    {
        return (string) config('app.env') === 'production' && ! (bool) $this->option('persist');
    }
}
