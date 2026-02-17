<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Ensure health endpoint is available.
     */
    public function test_health_endpoint_is_available(): void
    {
        $this->get('/up')->assertOk();
    }
}
