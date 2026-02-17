<template>
    <section class="card" style="max-width: 740px">
        <h1 style="margin-top: 0">Checkout</h1>
        <form class="grid" style="gap: 0.75rem" @submit.prevent="submitCheckout">
            <input v-model="form.email" type="email" placeholder="Email" required />
            <input v-model="form.coupon_code" placeholder="Coupon code (optional)" />
            <input v-model="form.billing_address.line1" placeholder="Billing address" required />
            <input v-model="form.billing_address.city" placeholder="Billing city" required />
            <input v-model="form.billing_address.country" placeholder="Billing country (2 letters)" maxlength="2" required />
            <input v-model="form.billing_address.postcode" placeholder="Billing postcode" required />
            <input v-model="form.shipping_address.line1" placeholder="Shipping address" required />
            <input v-model="form.shipping_address.city" placeholder="Shipping city" required />
            <input v-model="form.shipping_address.country" placeholder="Shipping country (2 letters)" maxlength="2" required />
            <input v-model="form.shipping_address.postcode" placeholder="Shipping postcode" required />
            <button class="btn btn-primary" type="submit">Place order</button>
        </form>

        <p v-if="resultMessage" style="margin-top: 1rem; font-weight: 600">{{ resultMessage }}</p>
    </section>
</template>

<script setup lang="ts">
import { reactive, ref } from 'vue';

import { apiClient } from '@/api/client';

const resultMessage = ref('');
const form = reactive({
    email: '',
    coupon_code: '',
    billing_address: {
        line1: '',
        city: '',
        country: 'US',
        postcode: '',
    },
    shipping_address: {
        line1: '',
        city: '',
        country: 'US',
        postcode: '',
    },
});

const submitCheckout = async (): Promise<void> => {
    try {
        const guestToken = localStorage.getItem('shop_guest_token');
        const payload = guestToken
            ? {
                  ...form,
                  guest_token: guestToken,
              }
            : { ...form };

        const { data } = await apiClient.post('/checkout/place-order', payload, {
            headers: {
                'Idempotency-Key': crypto.randomUUID(),
            },
        });

        resultMessage.value = `Order created: ${data.data.order_number}`;
    } catch {
        resultMessage.value = 'Checkout failed. Please verify account and cart.';
    }
};
</script>
