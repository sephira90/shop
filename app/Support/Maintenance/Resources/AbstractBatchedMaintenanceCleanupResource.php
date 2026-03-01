<?php

declare(strict_types=1);

namespace App\Support\Maintenance\Resources;

use App\Support\Data\TypedValue;
use App\Support\Maintenance\Contracts\MaintenanceCleanupResource;
use App\Support\Maintenance\Dto\MaintenanceCleanupResourceResultDto;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @template TModel of Model
 */
abstract class AbstractBatchedMaintenanceCleanupResource implements MaintenanceCleanupResource
{
    public function cleanup(
        CarbonImmutable $cutoff,
        bool $dryRun,
        int $batchSize,
    ): MaintenanceCleanupResourceResultDto {
        $matched = (int) $this->query($cutoff)->count();
        $predictedBatches = $this->batchCountFor($matched, $batchSize);

        if ($dryRun) {
            return new MaintenanceCleanupResourceResultDto(
                resource: $this->resource(),
                cutoffUtc: $cutoff->toIso8601String(),
                matched: $matched,
                affected: $matched,
                batches: $predictedBatches,
            );
        }

        $affected = 0;
        $actualBatches = 0;

        while (true) {
            $ids = $this->nextBatchIds($cutoff, $batchSize);

            if ($ids === []) {
                break;
            }

            $affected += $this->deleteBatch($cutoff, $ids);
            $actualBatches++;
        }

        return new MaintenanceCleanupResourceResultDto(
            resource: $this->resource(),
            cutoffUtc: $cutoff->toIso8601String(),
            matched: $matched,
            affected: $affected,
            batches: $actualBatches,
        );
    }

    /**
     * @return Builder<TModel>
     */
    abstract protected function query(CarbonImmutable $cutoff): Builder;

    /**
     * @return list<int|string>
     */
    private function nextBatchIds(CarbonImmutable $cutoff, int $batchSize): array
    {
        $query = $this->query($cutoff);
        $model = $query->getModel();

        return array_values($query
            ->orderBy($model->getQualifiedKeyName())
            ->limit($batchSize)
            ->pluck($model->getKeyName())
            ->map(static fn (mixed $id): int|string => is_int($id) ? $id : TypedValue::string($id))
            ->all());
    }

    /**
     * @param  list<int|string>  $ids
     */
    private function deleteBatch(CarbonImmutable $cutoff, array $ids): int
    {
        $query = $this->query($cutoff);
        $model = $query->getModel();

        return TypedValue::int($model->newQuery()->whereKey($ids)->delete());
    }

    private function batchCountFor(int $matched, int $batchSize): int
    {
        if ($matched <= 0) {
            return 0;
        }

        return (int) ceil($matched / $batchSize);
    }
}
