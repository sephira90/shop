import { defineStore } from "pinia";

import { apiClient } from "@/api/client";

export interface CartItem {
    product_variant_id: number;
    sku: string;
    name: string;
    quantity: number;
    unit_price: number;
    line_total: number;
}

interface CartPayload {
    id: string;
    guest_token: string | null;
    items: CartItem[];
    summary: {
        subtotal: number;
        total: number;
        shipping_total: number;
        discount_total: number;
    };
}

export const useCartStore = defineStore("cart", {
    state: () => ({
        cart: null as CartPayload | null,
        loading: false,
    }),
    getters: {
        itemCount: (state) => state.cart?.items.reduce((sum, item) => sum + item.quantity, 0) ?? 0,
    },
    actions: {
        async fetchCart(): Promise<void> {
            this.loading = true;
            try {
                const guestToken = localStorage.getItem("shop_guest_token");
                const { data } = await apiClient.get("/cart", {
                    params: guestToken ? { guest_token: guestToken } : {},
                });
                const cart = data.data as CartPayload;
                this.cart = cart;
                if (cart.guest_token) {
                    localStorage.setItem("shop_guest_token", cart.guest_token);
                }
            } finally {
                this.loading = false;
            }
        },
        async addOneItem(productVariantId: number): Promise<void> {
            if (!this.cart) {
                await this.fetchCart();
            }

            const currentQuantity =
                this.cart?.items.find((item) => item.product_variant_id === productVariantId)
                    ?.quantity ?? 0;

            await this.upsertItem(productVariantId, currentQuantity + 1);
        },
        async upsertItem(productVariantId: number, quantity: number): Promise<void> {
            const guestToken = localStorage.getItem("shop_guest_token");
            const { data } = await apiClient.post("/cart/items", {
                product_variant_id: productVariantId,
                quantity,
                guest_token: guestToken,
            });
            const cart = data.data as CartPayload;
            this.cart = cart;
            if (cart.guest_token) {
                localStorage.setItem("shop_guest_token", cart.guest_token);
            }
        },
        async removeItem(productVariantId: number): Promise<void> {
            const guestToken = localStorage.getItem("shop_guest_token");
            const { data } = await apiClient.delete(`/cart/items/${productVariantId}`, {
                params: guestToken ? { guest_token: guestToken } : {},
            });
            this.cart = data.data;
        },
    },
});
