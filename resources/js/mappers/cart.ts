import type { CartUpsertItemRequestDto, CartWireDto } from "@/contracts/api/v1/cart";
import type { CartPayload, CartUpsertItemPayload } from "@/types/cart";

export const mapCartFromApi = (value: CartWireDto): CartPayload => {
    return {
        id: value.id,
        guest_token: value.guest_token,
        currency: value.currency,
        status: value.status,
        items: value.items.map((item) => ({
            product_variant_id: item.product_variant_id,
            sku: item.sku ?? "",
            name: item.name ?? "",
            quantity: item.quantity,
            unit_price: item.unit_price,
            line_total: item.line_total,
        })),
        summary: {
            subtotal: value.summary.subtotal,
            total: value.summary.total,
            shipping_total: value.summary.shipping_total,
            discount_total: value.summary.discount_total,
        },
    };
};

export const toCartUpsertItemRequestDto = (
    payload: CartUpsertItemPayload,
): CartUpsertItemRequestDto => {
    const normalizedGuestToken = payload.guest_token?.trim();

    return {
        product_variant_id: payload.product_variant_id,
        quantity: payload.quantity,
        ...(normalizedGuestToken ? { guest_token: normalizedGuestToken } : {}),
    };
};
