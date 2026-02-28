<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Smoke\SmokeExecutionOptionsResolver;
use Tests\TestCase;

class SmokeExecutionOptionsResolverTest extends TestCase
{
    public function test_resolve_parses_persist_and_unique_selected_scenarios(): void
    {
        $resolver = new SmokeExecutionOptionsResolver;

        $options = $resolver->resolve([
            'persist' => true,
            'only' => 'catalog, checkout ,catalog',
        ]);

        $this->assertTrue($options->persist);
        $this->assertSame(['catalog', 'checkout'], $options->onlyScenarios);
    }
}
