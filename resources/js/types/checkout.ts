export interface CheckoutAddressForm {
    line1: string;
    city: string;
    country: string;
    postcode: string;
}

export interface CheckoutFormState {
    email: string;
    coupon_code: string;
    billing_address: CheckoutAddressForm;
    shipping_address: CheckoutAddressForm;
}

export interface CheckoutAddressPayload {
    line1: string;
    city: string;
    country: string;
    postcode: string;
}

export interface CheckoutPlaceOrderPayload {
    guest_token?: string;
    email: string;
    coupon_code: string | null;
    billing_address: CheckoutAddressPayload;
    shipping_address: CheckoutAddressPayload;
}

export interface CheckoutPaymentResult {
    payment_id: number;
    transaction_id: string;
    status: string;
    payload: Record<string, unknown>;
}

export interface CheckoutOrderResult {
    id: string;
    order_number: string;
    payment: CheckoutPaymentResult;
}
