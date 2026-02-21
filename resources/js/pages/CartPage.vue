<template>
    <section class="grid">
        <div class="card">
            <div class="stack stack--between">
                <h1 class="section-title">Cart</h1>
                <p class="order-total">
                    Total: {{ formatPrice(cartStore.cart?.summary.total ?? 0) }}
                </p>
            </div>

            <div v-if="cartStore.cart?.items.length">
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Qty</th>
                                <th>Price</th>
                                <th>Total</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="item in cartStore.cart?.items"
                                :key="item.product_variant_id"
                            >
                                <td>{{ item.name }}</td>
                                <td>
                                    <div class="actions">
                                        <button
                                            class="btn btn-muted"
                                            type="button"
                                            @click="decreaseQuantity(item)"
                                        >
                                            -
                                        </button>
                                        <input
                                            type="number"
                                            min="1"
                                            max="1000"
                                            step="1"
                                            :value="item.quantity"
                                            aria-label="Quantity"
                                            style="width: 5rem"
                                            @change="updateQuantity(item, $event)"
                                        />
                                        <button
                                            class="btn btn-muted"
                                            type="button"
                                            @click="increaseQuantity(item)"
                                        >
                                            +
                                        </button>
                                    </div>
                                </td>
                                <td>{{ formatPrice(item.unit_price) }}</td>
                                <td>{{ formatPrice(item.line_total) }}</td>
                                <td>
                                    <button
                                        class="btn btn-muted"
                                        type="button"
                                        @click="remove(item.product_variant_id)"
                                    >
                                        Remove
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="actions actions--top">
                    <RouterLink class="btn btn-primary" to="/checkout"
                        >Proceed to checkout</RouterLink
                    >
                </div>
            </div>

            <div v-else class="empty-state">
                <p>Cart is empty. Add products from the catalog.</p>
            </div>
        </div>
    </section>
</template>

<script setup lang="ts">
import { onMounted } from "vue";
import { RouterLink } from "vue-router";

import { type CartItem, useCartStore } from "@/stores/cart";

const cartStore = useCartStore();

const formatPrice = (value: number): string => {
    return Number(value).toFixed(2);
};

const remove = async (variantId: number): Promise<void> => {
    await cartStore.removeItem(variantId);
};

const increaseQuantity = async (item: CartItem): Promise<void> => {
    await cartStore.upsertItem(item.product_variant_id, item.quantity + 1);
};

const decreaseQuantity = async (item: CartItem): Promise<void> => {
    const nextQuantity = item.quantity - 1;

    if (nextQuantity <= 0) {
        await remove(item.product_variant_id);
        return;
    }

    await cartStore.upsertItem(item.product_variant_id, nextQuantity);
};

const updateQuantity = async (item: CartItem, event: unknown): Promise<void> => {
    const target = (event as { target?: { value?: string } }).target;

    if (!target || typeof target.value !== "string") {
        return;
    }

    const nextQuantity = Number.parseInt(target.value, 10);

    if (!Number.isFinite(nextQuantity)) {
        target.value = String(item.quantity);
        return;
    }

    const normalizedQuantity = Math.min(1000, Math.max(1, nextQuantity));
    target.value = String(normalizedQuantity);

    if (normalizedQuantity === item.quantity) {
        return;
    }

    await cartStore.upsertItem(item.product_variant_id, normalizedQuantity);
};

onMounted(async () => {
    await cartStore.fetchCart();
});
</script>
