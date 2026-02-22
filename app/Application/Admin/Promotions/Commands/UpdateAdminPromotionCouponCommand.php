<?php

declare(strict_types=1);

namespace App\Application\Admin\Promotions\Commands;

use App\Models\Coupon;

final readonly class UpdateAdminPromotionCouponCommand
{
    /**
     * Create command payload for admin promotion coupon update flow.
     *
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public Coupon $coupon,
        public array $payload,
    ) {}
}
