<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AppHealthCheckCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:healthcheck';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run database and cache health checks.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            DB::select('select 1 as ok');
            Cache::put('healthcheck:key', 'ok', 5);
            $cacheValue = Cache::get('healthcheck:key');
        } catch (\Throwable $exception) {
            $this->error('Health check failed: '.$exception->getMessage());

            return self::FAILURE;
        }

        if ($cacheValue !== 'ok') {
            $this->error('Health check failed: cache mismatch.');

            return self::FAILURE;
        }

        $this->info('Health check passed.');

        return self::SUCCESS;
    }
}
