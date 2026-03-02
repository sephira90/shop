import { ApiContractError } from "@/api/response";
import { createFieldParsers, isRecord } from "@/contracts/api/v1/assertions/primitives";
import type { CheckoutOrderWireDto, CheckoutPaymentWireDto } from "@/contracts/api/v1/checkout";

const { parseNullableString, requireNumber, requireString } = createFieldParsers("Checkout");

const parsePaymentPayload = (value: unknown): Record<string, unknown> => {
    if (!isRecord(value)) {
        throw new ApiContractError("Checkout payload field `payment.payload` must be object.");
    }

    return value;
};

const parsePayment = (value: unknown): CheckoutPaymentWireDto => {
    if (!isRecord(value)) {
        throw new ApiContractError("Checkout payload field `payment` must be object.");
    }

    return {
        payment_id: requireNumber(value, "payment_id"),
        transaction_id: requireString(value, "transaction_id"),
        status: parseNullableString(value, "status"),
        payload: parsePaymentPayload(value.payload),
    };
};

export const assertCheckoutOrderWireDto = (value: unknown): CheckoutOrderWireDto => {
    if (!isRecord(value)) {
        throw new ApiContractError("Checkout payload must be an object.");
    }

    return {
        id: requireString(value, "id"),
        order_number: requireString(value, "order_number"),
        payment: parsePayment(value.payment),
    };
};
