<template>
    <section class="grid" style="gap: 1rem">
        <div class="card">
            <h1 style="margin-top: 0">Cart</h1>
            <table class="table" v-if="cartStore.cart?.items.length">
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
                <tr v-for="item in cartStore.cart?.items" :key="item.product_variant_id">
                    <td>{{ item.name }}</td>
                    <td>{{ item.quantity }}</td>
                    <td>{{ item.unit_price }}</td>
                    <td>{{ item.line_total }}</td>
                    <td>
                        <button class="btn btn-muted" type="button" @click="remove(item.product_variant_id)">Remove</button>
                    </td>
                </tr>
                </tbody>
            </table>
            <p v-else>Cart is empty.</p>
            <p style="font-weight: 600">Total: {{ cartStore.cart?.summary.total ?? 0 }}</p>
            <RouterLink class="btn btn-primary" to="/checkout">Proceed to checkout</RouterLink>
        </div>
    </section>
</template>

<script setup lang="ts">
import { onMounted } from 'vue';
import { RouterLink } from 'vue-router';

import { useCartStore } from '@/stores/cart';

const cartStore = useCartStore();

const remove = async (variantId: number): Promise<void> => {
    await cartStore.removeItem(variantId);
};

onMounted(async () => {
    await cartStore.fetchCart();
});
</script>
