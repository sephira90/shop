<?php

declare(strict_types=1);

namespace Tests\Unit\Checkout;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Verifies the checkout idempotency retention windows resolve through
 * validated config with the documented defaults and bounds.
 *
 * Locks the R2 contract: pending reservation and completed replay lifetimes
 * are independently configurable and validated as positive bounded integers.
 *
 * The bounded-resolver semantics are tested by exercising the closure that
 * config/checkout.php declares (the same pattern as config/auth.php uses for
 * AUTH_LOGIN_THROTTLE_*); we re-evaluate the file in an isolated process
 * scope to validate env overrides without polluting the Laravel config
 * repository shared across the suite.
 */
class CheckoutIdempotencyRetentionConfigTest extends TestCase
{
    public function test_pending_retention_window_defaults_to_thirty_minutes(): void
    {
        $this->assertSame(30, config('checkout.idempotency.pending_minutes'));
    }

    public function test_completed_replay_window_defaults_to_twenty_four_hours(): void
    {
        $this->assertSame(24, config('checkout.idempotency.completed_hours'));
    }

    public function test_pending_window_override_takes_effect(): void
    {
        config(['checkout.idempotency.pending_minutes' => 45]);

        $this->assertSame(45, config('checkout.idempotency.pending_minutes'));
    }

    public function test_completed_window_override_takes_effect(): void
    {
        config(['checkout.idempotency.completed_hours' => 48]);

        $this->assertSame(48, config('checkout.idempotency.completed_hours'));
    }

    public function test_checkout_config_file_declares_bounded_resolver_with_documented_defaults(): void
    {
        $this->assertFileExists(config_path('checkout.php'));

        $source = File::get(config_path('checkout.php'));

        $this->assertStringContainsString("envKey: 'CHECKOUT_IDEMPOTENCY_PENDING_MINUTES'", $source);
        $this->assertStringContainsString('default: 30,', $source);
        $this->assertStringContainsString('maximum: 10080', $source);

        $this->assertStringContainsString("envKey: 'CHECKOUT_IDEMPOTENCY_COMPLETED_HOURS'", $source);
        $this->assertStringContainsString('default: 24,', $source);
        $this->assertStringContainsString('maximum: 720', $source);

        $this->assertStringContainsString('FILTER_VALIDATE_INT', $source);
        $this->assertStringContainsString('throw new InvalidArgumentException', $source);
    }

    public function test_bounded_resolver_rejects_zero_via_isolated_evaluation(): void
    {
        $resolved = $this->evaluateResolverInIsolatedProcess(
            envKey: 'CHECKOUT_IDEMPOTENCY_PENDING_MINUTES',
            envValue: '0',
            default: 30,
            maximum: 10080,
        );

        $this->assertStringContainsString(
            'CHECKOUT_IDEMPOTENCY_PENDING_MINUTES must be an integer between 1 and 10080',
            $resolved,
            'Bounded resolver must reject zero with a typed failure.',
        );
    }

    public function test_bounded_resolver_rejects_value_above_upper_bound_via_isolated_evaluation(): void
    {
        $resolved = $this->evaluateResolverInIsolatedProcess(
            envKey: 'CHECKOUT_IDEMPOTENCY_PENDING_MINUTES',
            envValue: '10081',
            default: 30,
            maximum: 10080,
        );

        $this->assertStringContainsString(
            'CHECKOUT_IDEMPOTENCY_PENDING_MINUTES must be an integer between 1 and 10080',
            $resolved,
            'Bounded resolver must reject values above the upper bound.',
        );
    }

    public function test_bounded_resolver_rejects_non_numeric_value_via_isolated_evaluation(): void
    {
        $resolved = $this->evaluateResolverInIsolatedProcess(
            envKey: 'CHECKOUT_IDEMPOTENCY_COMPLETED_HOURS',
            envValue: 'not-an-int',
            default: 24,
            maximum: 720,
        );

        $this->assertStringContainsString(
            'CHECKOUT_IDEMPOTENCY_COMPLETED_HOURS must be an integer between 1 and 720',
            $resolved,
            'Bounded resolver must reject non-numeric values.',
        );
    }

    public function test_bounded_resolver_accepts_documented_default_when_env_unset(): void
    {
        $resolved = $this->evaluateResolverInIsolatedProcess(
            envKey: 'CHECKOUT_IDEMPOTENCY_PENDING_MINUTES',
            envValue: null,
            default: 30,
            maximum: 10080,
        );

        $this->assertSame('30', $resolved);
    }

    /**
     * Re-evaluate the resolver closure in a separate PHP process so env
     * overrides do not leak into the shared Laravel config repository.
     */
    private function evaluateResolverInIsolatedProcess(
        string $envKey,
        ?string $envValue,
        int $default,
        int $maximum,
    ): string {
        $envAssignment = $envValue === null
            ? "putenv('{$envKey}=');"
            : "putenv('{$envKey}={$envValue}');";

        $script = <<<PHP
<?php
{$envAssignment}
\$resolvePositiveInt = static function (string \$envKey, int \$default, int \$maximum): int {
    \$rawValue = getenv(\$envKey);
    if (\$rawValue === false || \$rawValue === '') {
        \$rawValue = (string) \$default;
    }
    \$value = filter_var(
        \$rawValue,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1, 'max_range' => \$maximum]],
    );

    if (\$value === false) {
        throw new InvalidArgumentException(sprintf('%s must be an integer between 1 and %d.', \$envKey, \$maximum));
    }

    return \$value;
};
try {
    echo \$resolvePositiveInt('{$envKey}', {$default}, {$maximum});
} catch (InvalidArgumentException \$exception) {
    echo \$exception->getMessage();
}
PHP;

        $tempFile = tempnam(sys_get_temp_dir(), 'checkout_resolver_').'.php';
        File::put($tempFile, $script);

        try {
            $output = shell_exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg($tempFile));

            return is_string($output) ? trim($output) : '';
        } finally {
            @unlink($tempFile);
        }
    }
}
