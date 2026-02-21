import { beforeEach, describe, expect, it, vi } from "vitest";
import { createPinia, setActivePinia } from "pinia";

vi.mock("@/api/client", () => ({
    apiClient: {
        get: vi.fn(),
        post: vi.fn(),
        delete: vi.fn(),
    },
}));

import { apiClient } from "@/api/client";
import { useCartStore } from "@/stores/cart";

const apiClientMock = apiClient as unknown as {
    get: ReturnType<typeof vi.fn>;
    post: ReturnType<typeof vi.fn>;
    delete: ReturnType<typeof vi.fn>;
};

const buildCartPayload = (quantity: number) => ({
    id: "cart-1",
    guest_token: null,
    items: [
        {
            product_variant_id: 101,
            sku: "SKU-101",
            name: "Variant 101",
            quantity,
            unit_price: 15,
            line_total: 15 * quantity,
        },
    ],
    summary: {
        subtotal: 15 * quantity,
        total: 15 * quantity,
        shipping_total: 0,
        discount_total: 0,
    },
});

describe("cart store", () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();
        localStorage.clear();
    });

    it("starts with empty cart", () => {
        const cartStore = useCartStore();

        expect(cartStore.cart).toBeNull();
        expect(cartStore.itemCount).toBe(0);
    });

    it("increments existing item by one via addOneItem", async () => {
        apiClientMock.get.mockResolvedValueOnce({
            data: {
                data: buildCartPayload(2),
            },
        });

        apiClientMock.post.mockResolvedValueOnce({
            data: {
                data: buildCartPayload(3),
            },
        });

        const cartStore = useCartStore();

        await cartStore.addOneItem(101);

        expect(apiClientMock.get).toHaveBeenCalledWith("/cart", {
            params: {},
        });
        expect(apiClientMock.post).toHaveBeenCalledWith("/cart/items", {
            product_variant_id: 101,
            quantity: 3,
            guest_token: null,
        });
        expect(cartStore.cart?.items[0]?.quantity).toBe(3);
    });

    it("uses current store state for addOneItem without extra cart fetch", async () => {
        apiClientMock.post.mockResolvedValueOnce({
            data: {
                data: buildCartPayload(2),
            },
        });

        const cartStore = useCartStore();
        cartStore.cart = buildCartPayload(1);

        await cartStore.addOneItem(101);

        expect(apiClientMock.get).not.toHaveBeenCalled();
        expect(apiClientMock.post).toHaveBeenCalledWith("/cart/items", {
            product_variant_id: 101,
            quantity: 2,
            guest_token: null,
        });
        expect(cartStore.cart?.items[0]?.quantity).toBe(2);
    });
});
