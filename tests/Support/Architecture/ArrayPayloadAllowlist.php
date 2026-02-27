<?php

declare(strict_types=1);

namespace Tests\Support\Architecture;

final class ArrayPayloadAllowlist
{
    public const BASELINE_APPLICATION_ARRAY_PAYLOAD_COUNT = 0;

    public const BASELINE_APPLICATION_HANDLE_ARRAY_RETURN_COUNT = 0;

    public const BASELINE_FRONTEND_UNKNOWN_USAGE_COUNT = 5;

    /**
     * @return array<int, class-string>
     */
    public static function applicationArrayPayloadClasses(): array
    {
        return [];
    }

    /**
     * @return array<int, class-string>
     */
    public static function applicationHandleArrayReturnClasses(): array
    {
        return [];
    }

    /**
     * @return array<int, class-string>
     */
    public static function serviceArrayPayloadClasses(): array
    {
        return [];
    }
}
