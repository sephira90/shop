import { defineStore } from "pinia";

import { getCurrentCart, removeCartItem, upsertCartItem } from "@/api/cart";
import type { CartItem, CartPayload } from "@/types/cart";

export type { CartItem };

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
                const cart = await getCurrentCart(guestToken);

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
            const cart = await upsertCartItem({
                product_variant_id: productVariantId,
                quantity,
                ...(guestToken ? { guest_token: guestToken } : {}),
            });
            this.cart = cart;
            if (cart.guest_token) {
                localStorage.setItem("shop_guest_token", cart.guest_token);
            }
        },
        async removeItem(productVariantId: number): Promise<void> {
            const guestToken = localStorage.getItem("shop_guest_token");
            const cart = await removeCartItem(productVariantId, guestToken);

            this.cart = cart;
        },
    },
});
