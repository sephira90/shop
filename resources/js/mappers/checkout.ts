import type { CheckoutOrderResult, CheckoutPaymentResult } from "@/types/checkout";

import { asRecord, toNumber, toString } from "@/mappers/common";

const mapCheckoutPaymentFromApi = (value: unknown): CheckoutPaymentResult | null => {
    const record = asRecord(value);
    const paymentId = toNumber(record.payment_id);
    const transactionId = toString(record.transaction_id).trim();
    const status = toString(record.status).trim();

    if (paymentId <= 0 || transactionId === "" || status === "") {
        return null;
    }

    return {
        payment_id: paymentId,
        transaction_id: transactionId,
        status,
        payload: asRecord(record.payload),
    };
};

export const mapCheckoutOrderFromApi = (value: unknown): CheckoutOrderResult | null => {
    const record = asRecord(value);
    const orderId = toNumber(record.id);
    const orderNumber = toString(record.order_number).trim();
    const payment = mapCheckoutPaymentFromApi(record.payment);

    if (orderId <= 0 || orderNumber === "" || payment === null) {
        return null;
    }

    return {
        id: orderId,
        order_number: orderNumber,
        payment,
    };
};
