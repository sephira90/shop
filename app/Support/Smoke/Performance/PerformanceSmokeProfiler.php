<?php

declare(strict_types=1);

namespace App\Support\Smoke\Performance;

use App\Support\Smoke\Performance\Dto\PerformanceSmokeMeasurementDto;
use Illuminate\Support\Facades\DB;

final class PerformanceSmokeProfiler
{
    public function measure(string $name, callable $callback, bool $rollback = false): PerformanceSmokeMeasurementDto
    {
        $connection = DB::connection();
        $connection->flushQueryLog();
        $connection->enableQueryLog();

        if ($rollback) {
            DB::beginTransaction();
        }

        $startedAt = hrtime(true);

        try {
            $callback();
        } catch (\Throwable $exception) {
            if ($rollback && DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            $connection->disableQueryLog();

            throw $exception;
        }

        $durationMs = (hrtime(true) - $startedAt) / 1_000_000;
        $queries = count($connection->getQueryLog());

        if ($rollback && DB::transactionLevel() > 0) {
            DB::rollBack();
        }

        $connection->disableQueryLog();

        return new PerformanceSmokeMeasurementDto(
            name: $name,
            durationMs: $durationMs,
            queries: $queries,
        );
    }
}
