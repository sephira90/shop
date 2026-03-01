<?php

declare(strict_types=1);

namespace App\Http\Requests\Concerns;

use App\Support\Data\TypedValue;

trait NormalizesBooleanQueryInput
{
    /**
     * @param  list<string>  $fields
     */
    protected function normalizeBooleanQueryFields(array $fields): void
    {
        $this->normalizeBooleanInputFields($fields);
    }

    /**
     * @param  list<string>  $fields
     */
    protected function normalizeBooleanInputFields(array $fields): void
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->all();
        $changed = false;

        foreach ($fields as $field) {
            $segments = explode('.', $field);

            if ($this->normalizeBooleanValueAtPath($payload, $segments)) {
                $changed = true;
            }
        }

        if ($changed) {
            $this->replace(TypedValue::associativeArray($payload));
        }
    }

    /**
     * @param  array<int, string>  $segments
     */
    private function normalizeBooleanValueAtPath(mixed &$payload, array $segments): bool
    {
        if ($segments === []) {
            $parsed = $this->parseBooleanQueryValue($payload);

            if ($parsed === null || $payload === $parsed) {
                return false;
            }

            $payload = $parsed;

            return true;
        }

        /** @var string $segment */
        $segment = array_shift($segments);

        if ($segment === '*') {
            if (! is_array($payload)) {
                return false;
            }

            $changed = false;

            foreach ($payload as &$item) {
                if ($this->normalizeBooleanValueAtPath($item, $segments)) {
                    $changed = true;
                }
            }
            unset($item);

            return $changed;
        }

        if (! is_array($payload) || ! array_key_exists($segment, $payload)) {
            return false;
        }

        return $this->normalizeBooleanValueAtPath($payload[$segment], $segments);
    }

    private function parseBooleanQueryValue(mixed $value): ?bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return match ($value) {
                1 => true,
                0 => false,
                default => null,
            };
        }

        if (! is_string($value)) {
            return null;
        }

        return match (strtolower(trim($value))) {
            '1', 'true', 'on', 'yes' => true,
            '0', 'false', 'off', 'no' => false,
            default => null,
        };
    }
}
