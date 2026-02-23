<?php

declare(strict_types=1);

namespace App\Support\Smoke;

use Illuminate\Support\Facades\DB;

final class SmokePersistenceGuard
{
    /**
     * Execute smoke callback with optional production rollback safeguard.
     *
     * @template TResult
     *
     * @param  callable(): TResult  $callback
     * @return array{result: TResult, rolled_back: bool}
     */
    public function run(bool $shouldRollback, callable $callback): array
    {
        if (! $shouldRollback) {
            return [
                'result' => $callback(),
                'rolled_back' => false,
            ];
        }

        DB::beginTransaction();

        try {
            $result = $callback();
            DB::rollBack();
        } catch (\Throwable $exception) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            throw $exception;
        }

        return [
            'result' => $result,
            'rolled_back' => true,
        ];
    }
}
