<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use Tests\TestCase;

final class LegacyPayloadArtifactGuardrailTest extends TestCase
{
    public function test_legacy_payload_artifact_classes_are_not_available(): void
    {
        $this->assertFalse(class_exists('App\\Application\\Auth\\Support\\AuthUserPayloadBuilder'));
        $this->assertFalse(class_exists('App\\Application\\Checkout\\Commands\\PlaceCheckoutOrderResult'));
    }
}
