import type { CheckoutOrderWireDto } from "@/contracts/api/v1/checkout";
import type { CheckoutOrderResult, CheckoutPaymentResult } from "@/types/checkout";

const mapCheckoutPaymentFromApi = (
    value: CheckoutOrderWireDto["payment"],
): CheckoutPaymentResult | null => {
    const paymentId = value.payment_id;
    const transactionId = value.transaction_id.trim();
    const status = (value.status ?? "").trim();

    if (paymentId <= 0 || transactionId === "" || status === "") {
        return null;
    }

    return {
        payment_id: paymentId,
        transaction_id: transactionId,
        status,
        payload: value.payload,
    };
};

export const mapCheckoutOrderFromApi = (
    value: CheckoutOrderWireDto,
): CheckoutOrderResult | null => {
    const orderId = value.id.trim();
    const orderNumber = value.order_number.trim();
    const payment = mapCheckoutPaymentFromApi(value.payment);

    if (orderId === "" || orderNumber === "" || payment === null) {
        return null;
    }

    return {
        id: orderId,
        order_number: orderNumber,
        payment,
    };
};
