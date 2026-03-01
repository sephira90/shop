<?php

declare(strict_types=1);

namespace App\Support\Data;

use Stringable;
use UnexpectedValueException;

final class TypedValue
{
    public static function string(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value) || is_bool($value) || $value instanceof Stringable) {
            return (string) $value;
        }

        throw new UnexpectedValueException('Expected string-compatible value.');
    }

    public static function trimmedString(mixed $value): string
    {
        return trim(self::string($value));
    }

    public static function nullableTrimmedString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = self::trimmedString($value);

        return $normalized === '' ? null : $normalized;
    }

    public static function int(mixed $value): int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && preg_match('/^-?\d+$/', trim($value)) === 1) {
            return (int) trim($value);
        }

        if (is_float($value) && floor($value) === $value) {
            return (int) $value;
        }

        throw new UnexpectedValueException('Expected int-compatible value.');
    }

    public static function float(mixed $value): float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        if (is_string($value) && is_numeric(trim($value))) {
            return (float) trim($value);
        }

        throw new UnexpectedValueException('Expected float-compatible value.');
    }

    public static function bool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return match ($value) {
                0 => false,
                1 => true,
                default => throw new UnexpectedValueException('Expected bool-compatible value.'),
            };
        }

        if (is_string($value)) {
            return match (strtolower(trim($value))) {
                '0', 'false', 'off', 'no' => false,
                '1', 'true', 'on', 'yes' => true,
                default => throw new UnexpectedValueException('Expected bool-compatible value.'),
            };
        }

        throw new UnexpectedValueException('Expected bool-compatible value.');
    }

    /**
     * @return array<string, mixed>
     */
    public static function associativeArray(mixed $value): array
    {
        if (! is_array($value)) {
            throw new UnexpectedValueException('Expected array payload.');
        }

        $normalized = [];

        foreach ($value as $key => $item) {
            $normalized[(string) $key] = $item;
        }

        return $normalized;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function associativeArrayOrNull(mixed $value): ?array
    {
        if ($value === null) {
            return null;
        }

        return self::associativeArray($value);
    }

    /**
     * @return list<mixed>
     */
    public static function list(mixed $value): array
    {
        if (! is_array($value)) {
            throw new UnexpectedValueException('Expected list payload.');
        }

        return array_values($value);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function listOfAssociativeArrays(mixed $value): array
    {
        $items = self::list($value);
        $normalized = [];

        foreach ($items as $item) {
            $normalized[] = self::associativeArray($item);
        }

        return $normalized;
    }

    /**
     * @return list<string>
     */
    public static function stringList(mixed $value): array
    {
        $items = self::list($value);
        $normalized = [];

        foreach ($items as $item) {
            $normalized[] = self::string($item);
        }

        return $normalized;
    }
}
