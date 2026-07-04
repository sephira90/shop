<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class HttpsEnforcementTest extends TestCase
{
    public function test_http_request_redirects_to_https_when_force_https_enabled_in_non_local_env(): void
    {
        $this->app['env'] = 'production';
        config()->set('security.force_https', true);

        $this->call('GET', '/api/v1/catalog/products', [], [], [], [
            'HTTPS' => false,
            'SERVER_PORT' => 80,
        ])
            ->assertRedirect();
    }

    public function test_http_request_passes_through_when_app_env_is_local(): void
    {
        $this->app['env'] = 'local';
        config()->set('security.force_https', true);

        $response = $this->getJson('/api/v1/catalog/products');

        // Local env must not redirect to HTTPS even when force_https is globally enabled.
        // Status is whatever the catalog endpoint returns (200 or 422), not a 3xx redirect.
        $this->assertNotSame(301, $response->status());
        $this->assertNotSame(302, $response->status());
    }

    public function test_request_with_forwarded_https_proto_does_not_redirect(): void
    {
        $this->app['env'] = 'production';
        config()->set('security.force_https', true);

        $response = $this->call('GET', '/api/v1/catalog/products', [], [], [], [
            'HTTPS' => false,
            'SERVER_PORT' => 80,
            'HTTP_X_FORWARDED_PROTO' => 'https',
        ]);

        $this->assertNotSame(301, $response->status());
        $this->assertNotSame(302, $response->status());
    }
}
