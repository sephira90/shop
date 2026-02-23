<template>
    <AppCard tag="section">
        <CheckoutHeader />

        <AppFormShell @submit="submitCheckout">
            <CheckoutContactFields
                v-model:email="form.email"
                v-model:coupon-code="form.coupon_code"
            />

            <AppGridTwoColumns>
                <CheckoutAddressCard
                    title="Billing address"
                    v-model:address="form.billing_address"
                />
                <CheckoutAddressCard
                    title="Shipping address"
                    v-model:address="form.shipping_address"
                />
            </AppGridTwoColumns>

            <AppButton variant="primary" type="submit" :disabled="isSubmitting">
                {{ isSubmitting ? "Placing order..." : "Place order" }}
            </AppButton>
        </AppFormShell>

        <CheckoutResultNotice
            v-if="resultMessage"
            :message="resultMessage"
            :is-success="isResultSuccess"
        />
    </AppCard>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from "vue";

import { placeCheckoutOrder } from "@/api/checkout";
import CheckoutAddressCard from "@/components/checkout/CheckoutAddressCard.vue";
import CheckoutContactFields from "@/components/checkout/CheckoutContactFields.vue";
import CheckoutHeader from "@/components/checkout/CheckoutHeader.vue";
import CheckoutResultNotice from "@/components/checkout/CheckoutResultNotice.vue";
import AppButton from "@/components/ui/actions/AppButton.vue";
import AppCard from "@/components/ui/layout/AppCard.vue";
import AppFormShell from "@/components/ui/forms/AppFormShell.vue";
import AppGridTwoColumns from "@/components/ui/layout/AppGridTwoColumns.vue";
import { useApiError } from "@/composables/useApiError";
import { useAuthStore } from "@/stores/auth";
import { useCartStore } from "@/stores/cart";
import {
    buildCheckoutIdempotencyKey,
    buildCheckoutPayload,
    createCheckoutFormState,
} from "@/validators/checkout";

const resultMessage = ref("");
const isSubmitting = ref(false);
const authStore = useAuthStore();
const cartStore = useCartStore();
const { parseApiError } = useApiError();
const form = reactive(createCheckoutFormState());
const isResultSuccess = computed((): boolean => resultMessage.value.startsWith("Order created"));

const submitCheckout = async (): Promise<void> => {
    isSubmitting.value = true;

    try {
        const guestToken = (
            localStorage.getItem("shop_guest_token") ??
            cartStore.cart?.guest_token ??
            ""
        ).trim();
        const isAuthenticated = authStore.isAuthenticated;

        if (!isAuthenticated && guestToken === "") {
            resultMessage.value = "Guest token is missing. Open cart and try checkout again.";
            return;
        }
        const payload = buildCheckoutPayload(form, guestToken === "" ? null : guestToken);
        const order = await placeCheckoutOrder(payload, buildCheckoutIdempotencyKey());
        resultMessage.value = order
            ? `Order created: ${order.order_number}`
            : "Order created successfully.";
        await cartStore.fetchCart();
    } catch (error: unknown) {
        resultMessage.value = parseApiError(
            error,
            "Checkout failed. Please verify account and cart.",
        );
    } finally {
        isSubmitting.value = false;
    }
};

onMounted(async () => {
    await Promise.all([cartStore.fetchCart(), authStore.ensureUserLoaded()]);
});

watch(
    () => cartStore.cart?.guest_token,
    (guestToken) => {
        if (!guestToken || guestToken.trim() === "") {
            return;
        }

        const existing = localStorage.getItem("shop_guest_token");

        if (!existing || existing.trim() === "") {
            localStorage.setItem("shop_guest_token", guestToken);
        }
    },
);

watch(
    () => authStore.user?.email,
    (email) => {
        if (form.email.trim() === "" && email) {
            form.email = email;
        }
    },
    { immediate: true },
);
</script>
