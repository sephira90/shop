<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Smoke\SmokeScenarioSelector;
use InvalidArgumentException;
use Tests\TestCase;

class SmokeScenarioSelectorTest extends TestCase
{
    public function test_select_returns_subset_in_requested_order(): void
    {
        $selector = new SmokeScenarioSelector;
        $scenarios = [
            'catalog' => (object) ['name' => 'catalog'],
            'checkout' => (object) ['name' => 'checkout'],
        ];

        $selected = $selector->select($scenarios, ['checkout', 'catalog'], 'api smoke');

        $this->assertSame('checkout', $selected[0]->name);
        $this->assertSame('catalog', $selected[1]->name);
    }

    public function test_select_rejects_unknown_scenario_name(): void
    {
        $selector = new SmokeScenarioSelector;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Option --only contains unknown api smoke scenario "missing".');

        $selector->select(['catalog' => (object) []], ['missing'], 'api smoke');
    }
}
