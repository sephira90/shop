<template>
    <section class="card">
        <h1 class="section-title">Checkout</h1>
        <p class="muted">Fill billing and shipping details to create a new order.</p>

        <form class="grid actions--top" @submit.prevent="submitCheckout">
            <input v-model="form.email" type="email" placeholder="Email" required />
            <input v-model="form.coupon_code" placeholder="Coupon code (optional)" />

            <div class="grid grid-2">
                <div class="card">
                    <h2>Billing address</h2>
                    <div class="grid">
                        <input v-model="form.billing_address.line1" placeholder="Address line" required />
                        <input v-model="form.billing_address.city" placeholder="City" required />
                        <input
                            v-model="form.billing_address.country"
                            placeholder="Country (2 letters)"
                            maxlength="2"
                            required
                        />
                        <input v-model="form.billing_address.postcode" placeholder="Postcode" required />
                    </div>
                </div>

                <div class="card">
                    <h2>Shipping address</h2>
                    <div class="grid">
                        <input v-model="form.shipping_address.line1" placeholder="Address line" required />
                        <input v-model="form.shipping_address.city" placeholder="City" required />
                        <input
                            v-model="form.shipping_address.country"
                            placeholder="Country (2 letters)"
                            maxlength="2"
                            required
                        />
                        <input v-model="form.shipping_address.postcode" placeholder="Postcode" required />
                    </div>
                </div>
            </div>

            <button class="btn btn-primary" type="submit" :disabled="isSubmitting">
                {{ isSubmitting ? 'Placing order...' : 'Place order' }}
            </button>
        </form>

        <p
            v-if="resultMessage"
            :class="[
                'notice',
                resultMessage.startsWith('Order created') ? 'notice--success' : 'notice--error',
            ]"
        >
            {{ resultMessage }}
        </p>
    </section>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref, watch } from 'vue';

import { placeCheckoutOrder } from '@/api/checkout';
import { useApiError } from '@/composables/useApiError';
import { useAuthStore } from '@/stores/auth';
import { useCartStore } from '@/stores/cart';
import { buildCheckoutIdempotencyKey, buildCheckoutPayload, createCheckoutFormState } from '@/validators/checkout';

const resultMessage = ref('');
const isSubmitting = ref(false);
const authStore = useAuthStore();
const cartStore = useCartStore();
const { parseApiError } = useApiError();
const form = reactive(createCheckoutFormState());

const submitCheckout = async (): Promise<void> => {
    isSubmitting.value = true;

    try {
        const guestToken = (localStorage.getItem('shop_guest_token') ?? cartStore.cart?.guest_token ?? '').trim();
        const isAuthenticated = authStore.isAuthenticated;

        if (!isAuthenticated && guestToken === '') {
            resultMessage.value = 'Guest token is missing. Open cart and try checkout again.';
            return;
        }
        const payload = buildCheckoutPayload(form, guestToken === '' ? null : guestToken);
        const order = await placeCheckoutOrder(payload, buildCheckoutIdempotencyKey());
        resultMessage.value = order ? `Order created: ${order.order_number}` : 'Order created successfully.';
        await cartStore.fetchCart();
    } catch (error: unknown) {
        resultMessage.value = parseApiError(error, 'Checkout failed. Please verify account and cart.');
    } finally {
        isSubmitting.value = false;
    }
};

onMounted(async () => {
    await Promise.all([
        cartStore.fetchCart(),
        authStore.ensureUserLoaded(),
    ]);
});

watch(
    () => cartStore.cart?.guest_token,
    (guestToken) => {
        if (!guestToken || guestToken.trim() === '') {
            return;
        }

        const existing = localStorage.getItem('shop_guest_token');

        if (!existing || existing.trim() === '') {
            localStorage.setItem('shop_guest_token', guestToken);
        }
    },
);

watch(
    () => authStore.user?.email,
    (email) => {
        if (form.email.trim() === '' && email) {
            form.email = email;
        }
    },
    { immediate: true },
);
</script>
