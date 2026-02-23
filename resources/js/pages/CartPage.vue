<template>
    <section class="grid">
        <AppCard>
            <CartSummaryHeader :total="cartStore.cart?.summary.total ?? 0" />

            <CartItemsTable
                v-if="hasItems"
                :items="cartStore.cart?.items ?? []"
                @remove-item="remove"
                @increase-quantity="increaseQuantity"
                @decrease-quantity="decreaseQuantity"
                @update-quantity="updateQuantity"
            />

            <CartEmptyState v-else />
        </AppCard>
    </section>
</template>

<script setup lang="ts">
import { computed, onMounted } from "vue";

import CartEmptyState from "@/components/cart/CartEmptyState.vue";
import CartItemsTable from "@/components/cart/CartItemsTable.vue";
import CartSummaryHeader from "@/components/cart/CartSummaryHeader.vue";
import AppCard from "@/components/ui/AppCard.vue";
import { type CartItem, useCartStore } from "@/stores/cart";

const cartStore = useCartStore();

const hasItems = computed(() => Boolean(cartStore.cart?.items.length));

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

const updateQuantity = async (payload: { item: CartItem; quantity: number }): Promise<void> => {
    if (payload.quantity === payload.item.quantity) {
        return;
    }

    await cartStore.upsertItem(payload.item.product_variant_id, payload.quantity);
};

onMounted(async () => {
    await cartStore.fetchCart();
});
</script>
