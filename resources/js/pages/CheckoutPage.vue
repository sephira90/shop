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
import { onMounted } from "vue";

import CheckoutAddressCard from "@/components/checkout/CheckoutAddressCard.vue";
import CheckoutContactFields from "@/components/checkout/CheckoutContactFields.vue";
import CheckoutHeader from "@/components/checkout/CheckoutHeader.vue";
import CheckoutResultNotice from "@/components/checkout/CheckoutResultNotice.vue";
import AppButton from "@/components/ui/actions/AppButton.vue";
import { createBrowserCheckoutGuestTokenStorage } from "@/composables/checkout/checkoutPageEffects";
import { useCheckoutPageViewModel } from "@/composables/checkout/useCheckoutPageViewModel";
import AppCard from "@/components/ui/layout/AppCard.vue";
import AppFormShell from "@/components/ui/forms/AppFormShell.vue";
import AppGridTwoColumns from "@/components/ui/layout/AppGridTwoColumns.vue";

const { form, isSubmitting, resultMessage, isResultSuccess, initialize, submitCheckout } =
    useCheckoutPageViewModel({
        guestTokenStorage: createBrowserCheckoutGuestTokenStorage(),
    });

onMounted(async () => {
    await initialize();
});
</script>
