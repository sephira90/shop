import { apiClient } from '@/api/client';
import { asRecord, toString } from '@/mappers/common';
import type { CheckoutOrderResult, CheckoutPlaceOrderPayload } from '@/types/checkout';

export const placeCheckoutOrder = async (
    payload: CheckoutPlaceOrderPayload,
    idempotencyKey: string,
): Promise<CheckoutOrderResult | null> => {
    const { data } = await apiClient.post('/checkout/place-order', payload, {
        headers: {
            'Idempotency-Key': idempotencyKey,
        },
    });
    const record = asRecord(data);
    const order = asRecord(record.data);
    const orderNumber = toString(order.order_number).trim();

    if (orderNumber === '') {
        return null;
    }

    return {
        order_number: orderNumber,
    };
};
