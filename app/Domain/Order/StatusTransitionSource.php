<?php

declare(strict_types=1);

namespace App\Domain\Order;

enum StatusTransitionSource: string
{
    case PAYMENT_WEBHOOK = 'payment_webhook';
    case SHIPPING_WEBHOOK = 'shipping_webhook';
    case ADMIN_ORDER_UPDATE = 'admin_order_update';
}
