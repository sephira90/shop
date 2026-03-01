<?php

declare(strict_types=1);

namespace Tests\Unit\Account;

use App\Application\Account\Orders\Dto\AccountOrderSummaryAggregateDto;
use App\Application\Account\Orders\Dto\AccountOrderSummaryStatusGroupDto;
use App\Application\Account\Orders\Support\AccountOrderSummaryProjector;
use Tests\TestCase;

class AccountOrderSummaryProjectorTest extends TestCase
{
    public function test_project_counts_paid_and_in_delivery_orders_without_double_counting_paid_capture_overlap(): void
    {
        $aggregate = new AccountOrderSummaryAggregateDto(
            totalOrders: 5,
            totalSpent: 250.0,
            statusGroups: [
                new AccountOrderSummaryStatusGroupDto('paid', 'captured', 'packed', 1),
                new AccountOrderSummaryStatusGroupDto('processing', 'authorized', 'shipped', 1),
                new AccountOrderSummaryStatusGroupDto('pending', 'pending', 'pending', 1),
                new AccountOrderSummaryStatusGroupDto('paid', 'captured', 'delivered', 2),
            ],
        );

        $summary = $this->app->make(AccountOrderSummaryProjector::class)->project($aggregate);

        $this->assertSame(5, $summary->totalOrders);
        $this->assertSame(3, $summary->paidOrders);
        $this->assertSame(2, $summary->inDeliveryOrders);
        $this->assertSame(250.0, $summary->totalSpent);
    }
}
