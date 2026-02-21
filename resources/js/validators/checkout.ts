import type {
    CheckoutAddressForm,
    CheckoutAddressPayload,
    CheckoutFormState,
    CheckoutPlaceOrderPayload,
} from "@/types/checkout";

const normalizeAddress = (address: CheckoutAddressForm): CheckoutAddressPayload => {
    return {
        line1: address.line1.trim(),
        city: address.city.trim(),
        country: address.country.trim().toUpperCase(),
        postcode: address.postcode.trim(),
    };
};

export const createCheckoutFormState = (email = ""): CheckoutFormState => ({
    email: email.trim(),
    coupon_code: "",
    billing_address: {
        line1: "",
        city: "",
        country: "US",
        postcode: "",
    },
    shipping_address: {
        line1: "",
        city: "",
        country: "US",
        postcode: "",
    },
});

export const buildCheckoutPayload = (
    form: CheckoutFormState,
    guestToken: string | null,
): CheckoutPlaceOrderPayload => {
    const payload: CheckoutPlaceOrderPayload = {
        email: form.email.trim(),
        coupon_code: form.coupon_code.trim() === "" ? null : form.coupon_code.trim(),
        billing_address: normalizeAddress(form.billing_address),
        shipping_address: normalizeAddress(form.shipping_address),
    };
    const normalizedGuestToken = (guestToken ?? "").trim();

    if (normalizedGuestToken !== "") {
        payload.guest_token = normalizedGuestToken;
    }

    return payload;
};

export const buildCheckoutIdempotencyKey = (): string => {
    if (
        typeof globalThis.crypto !== "undefined" &&
        typeof globalThis.crypto.randomUUID === "function"
    ) {
        return globalThis.crypto.randomUUID();
    }

    return `checkout-${Date.now()}-${Math.random().toString(16).slice(2)}`;
};
