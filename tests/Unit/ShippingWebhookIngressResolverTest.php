<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Shipping\ShippingWebhookIngressResolver;
use App\Services\Webhook\WebhookIngressException;
use App\Support\Data\JsonPayload;
use Tests\TestCase;

class ShippingWebhookIngressResolverTest extends TestCase
{
    public function test_resolve_extracts_provider_identifiers_into_typed_payload(): void
    {
        $resolvedPayload = app(ShippingWebhookIngressResolver::class)->resolve(JsonPayload::fromArray([
            'event_id' => ' evt-shipping-1 ',
            'tracking_number' => ' trk-shipping-1 ',
            'status' => ' delivered ',
        ]));

        $this->assertSame('evt-shipping-1', $resolvedPayload->eventId);
        $this->assertSame('trk-shipping-1', $resolvedPayload->trackingNumber);
        $this->assertSame('delivered', $resolvedPayload->status);
    }

    public function test_prevalidate_resolved_returns_metadata_for_valid_signature(): void
    {
        $resolver = app(ShippingWebhookIngressResolver::class);
        $resolvedPayload = $resolver->resolve(JsonPayload::fromArray([
            'event_id' => 'evt-shipping-valid',
            'tracking_number' => 'trk-shipping-valid',
            'status' => 'shipped',
        ]));

        $metadata = $resolver->prevalidateResolved(
            $resolvedPayload,
            hash('sha256', 'evt-shipping-valid'),
        );

        $this->assertSame('evt-shipping-valid', $metadata->eventId);
    }

    public function test_prevalidate_resolved_rejects_invalid_signature(): void
    {
        $resolver = app(ShippingWebhookIngressResolver::class);
        $resolvedPayload = $resolver->resolve(JsonPayload::fromArray([
            'event_id' => 'evt-shipping-invalid-signature',
            'tracking_number' => 'trk-shipping-invalid-signature',
            'status' => 'shipped',
        ]));

        $this->expectException(WebhookIngressException::class);
        $this->expectExceptionMessage('Invalid shipping webhook signature.');

        $resolver->prevalidateResolved($resolvedPayload, 'invalid-signature');
    }

    public function test_prevalidate_resolved_rejects_missing_tracking_number(): void
    {
        $resolver = app(ShippingWebhookIngressResolver::class);
        $resolvedPayload = $resolver->resolve(JsonPayload::fromArray([
            'event_id' => 'evt-shipping-no-tracking',
            'status' => 'shipped',
        ]));

        $this->expectException(WebhookIngressException::class);
        $this->expectExceptionMessage('Tracking number is required.');

        $resolver->prevalidateResolved(
            $resolvedPayload,
            hash('sha256', 'evt-shipping-no-tracking'),
        );
    }
}
