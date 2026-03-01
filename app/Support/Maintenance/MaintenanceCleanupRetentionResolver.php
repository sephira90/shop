<?php

declare(strict_types=1);

namespace App\Support\Maintenance;

use App\Support\Data\TypedValue;
use App\Support\Maintenance\Dto\MaintenanceCleanupRetentionDto;
use InvalidArgumentException;

final class MaintenanceCleanupRetentionResolver
{
    /**
     * @param  array{
     *      idempotency-retain-hours:mixed,
     *      webhook-retain-hours:mixed,
     *      active-cart-retain-hours:mixed,
     *      inactive-cart-retain-hours:mixed
     *  }  $options
     */
    public function resolve(array $options): MaintenanceCleanupRetentionDto
    {
        return new MaintenanceCleanupRetentionDto(
            idempotencyHours: $this->resolvePositiveIntOption(
                $options['idempotency-retain-hours'],
                TypedValue::int(config('cleanup.retention.idempotency_hours', 168)),
                'idempotency-retain-hours',
            ),
            webhookHours: $this->resolvePositiveIntOption(
                $options['webhook-retain-hours'],
                TypedValue::int(config('cleanup.retention.webhook_hours', 720)),
                'webhook-retain-hours',
            ),
            activeCartHours: $this->resolvePositiveIntOption(
                $options['active-cart-retain-hours'],
                TypedValue::int(config('cleanup.retention.active_cart_hours', 720)),
                'active-cart-retain-hours',
            ),
            inactiveCartHours: $this->resolvePositiveIntOption(
                $options['inactive-cart-retain-hours'],
                TypedValue::int(config('cleanup.retention.inactive_cart_hours', 168)),
                'inactive-cart-retain-hours',
            ),
        );
    }

    /**
     * @throws InvalidArgumentException
     */
    private function resolvePositiveIntOption(mixed $raw, int $fallback, string $label): int
    {
        if ($raw === null || TypedValue::trimmedString($raw) === '') {
            if ($fallback > 0) {
                return $fallback;
            }

            throw new InvalidArgumentException(sprintf('Configured "%s" must be greater than 0.', $label));
        }

        $parsed = filter_var($raw, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);
        if ($parsed === false) {
            throw new InvalidArgumentException(sprintf('Option --%s must be a positive integer.', $label));
        }

        return (int) $parsed;
    }
}
