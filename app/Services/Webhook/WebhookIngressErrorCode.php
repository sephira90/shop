<?php

declare(strict_types=1);

namespace App\Services\Webhook;

enum WebhookIngressErrorCode: string
{
    case INVALID_SIGNATURE = 'invalid_signature';

    case MISSING_EVENT_ID = 'missing_event_id';

    case MISSING_PAYMENT_TRANSACTION_ID = 'missing_payment_transaction_id';

    case MISSING_SHIPPING_TRACKING_NUMBER = 'missing_shipping_tracking_number';

    case PAYLOAD_HASH_MISMATCH = 'payload_hash_mismatch';

    case PAYMENT_NOT_FOUND = 'payment_not_found';

    case PAYMENT_ORDER_NOT_FOUND = 'payment_order_not_found';

    case SHIPMENT_NOT_FOUND = 'shipment_not_found';

    case SHIPMENT_ORDER_NOT_FOUND = 'shipment_order_not_found';

    case REJECTED_TRANSITION = 'rejected_transition';
}
