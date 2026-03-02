import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { createPinia, setActivePinia } from "pinia";

vi.mock("@/api/client", () => ({
    apiClient: {
        get: vi.fn(),
        post: vi.fn(),
        delete: vi.fn(),
    },
}));

import { apiClient } from "@/api/client";
import {
    resetCartStoreStorageAdapterForTests,
    setCartStoreStorageAdapterForTests,
    useCartStore,
} from "@/stores/cart";
import { createInMemoryStorageAdapter } from "@/utils/storage";

const apiClientMock = apiClient as unknown as {
    get: ReturnType<typeof vi.fn>;
    post: ReturnType<typeof vi.fn>;
    delete: ReturnType<typeof vi.fn>;
};

const buildCartPayload = (quantity: number, guestToken: string | null = null) => ({
    id: "cart-1",
    guest_token: guestToken,
    currency: "USD",
    status: "active",
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
        setCartStoreStorageAdapterForTests(createInMemoryStorageAdapter());
    });

    afterEach(() => {
        resetCartStoreStorageAdapterForTests();
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
        });
        expect(cartStore.cart?.items[0]?.quantity).toBe(2);
    });

    it("reuses persisted guest token for subsequent mutations", async () => {
        apiClientMock.get.mockResolvedValueOnce({
            data: {
                data: buildCartPayload(1, "guest-token-1"),
            },
        });
        apiClientMock.post.mockResolvedValueOnce({
            data: {
                data: buildCartPayload(2, "guest-token-1"),
            },
        });

        const cartStore = useCartStore();

        await cartStore.fetchCart();
        await cartStore.upsertItem(101, 2);

        expect(apiClientMock.post).toHaveBeenCalledWith("/cart/items", {
            product_variant_id: 101,
            quantity: 2,
            guest_token: "guest-token-1",
        });
    });
});
