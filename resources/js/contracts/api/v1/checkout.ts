export interface CheckoutPaymentWireDto {
    payment_id: number;
    transaction_id: string;
    status: string | null;
    payload: Record<string, unknown>;
}

export interface CheckoutOrderWireDto {
    id: string;
    order_number: string;
    payment: CheckoutPaymentWireDto;
}
