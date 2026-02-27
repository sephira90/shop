import { ApiContractError } from "@/api/response";
import type { CheckoutOrderWireDto, CheckoutPaymentWireDto } from "@/contracts/api/v1/checkout";

const isRecord = (value: unknown): value is Record<string, unknown> =>
    typeof value === "object" && value !== null && !Array.isArray(value);

const requireString = (record: Record<string, unknown>, key: string): string => {
    const value = record[key];

    if (typeof value !== "string") {
        throw new ApiContractError(`Checkout payload field \`${key}\` must be string.`);
    }

    return value;
};

const parseNullableString = (record: Record<string, unknown>, key: string): string | null => {
    const value = record[key];

    if (value === null) {
        return null;
    }

    if (typeof value !== "string") {
        throw new ApiContractError(`Checkout payload field \`${key}\` must be string|null.`);
    }

    return value;
};

const requireNumber = (record: Record<string, unknown>, key: string): number => {
    const value = Number(record[key]);

    if (!Number.isFinite(value)) {
        throw new ApiContractError(`Checkout payload field \`${key}\` must be number.`);
    }

    return value;
};

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
