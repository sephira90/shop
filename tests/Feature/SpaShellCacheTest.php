<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpaShellCacheTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Ensure SPA shell route is cacheable and does not start session.
     */
    public function test_spa_shell_route_is_publicly_cacheable_without_session_cookie(): void
    {
        $this->withoutVite();

        $response = $this->get('/');
        $cacheControl = (string) $response->headers->get('Cache-Control');

        $response->assertOk()->assertHeader('Cache-Control');
        $this->assertStringContainsString('public', $cacheControl);
        $this->assertStringContainsString('max-age=300', $cacheControl);
        $this->assertFalse($response->headers->has('set-cookie'));
    }
}
