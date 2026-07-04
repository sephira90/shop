<?php

declare(strict_types=1);

$resolvePositiveBool = static function (string $key, bool $default): bool {
    $value = env($key);

    if ($value === null) {
        return $default;
    }

    if (is_bool($value)) {
        return $value;
    }

    $normalized = strtolower((string) $value);

    return in_array($normalized, ['true', '1', 'yes', 'on'], true);
};

return [

    'force_https' => $resolvePositiveBool('APP_FORCE_HTTPS', true),

    'trusted_proxies' => env('APP_TRUSTED_PROXIES', '*'),

    'trusted_hosts' => array_values(array_filter(array_map(trim(...), explode(',', (string) env('APP_TRUSTED_HOSTS', ''))))),

];
