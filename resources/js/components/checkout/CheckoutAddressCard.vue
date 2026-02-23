<template>
    <AppCard>
        <h2>{{ title }}</h2>
        <div class="grid">
            <AppTextInput v-model="line1Model" placeholder="Address line" required />
            <AppTextInput v-model="cityModel" placeholder="City" required />
            <AppTextInput
                v-model="countryModel"
                placeholder="Country (2 letters)"
                maxlength="2"
                required
            />
            <AppTextInput v-model="postcodeModel" placeholder="Postcode" required />
        </div>
    </AppCard>
</template>

<script setup lang="ts">
import { computed } from "vue";

import AppCard from "@/components/ui/AppCard.vue";
import AppTextInput from "@/components/ui/AppTextInput.vue";
import type { CheckoutAddressForm } from "@/types/checkout";

const props = defineProps<{
    title: string;
    address: CheckoutAddressForm;
}>();

const emit = defineEmits<{
    (event: "update:address", value: CheckoutAddressForm): void;
}>();

const updateField = (key: keyof CheckoutAddressForm, value: string): void => {
    emit("update:address", {
        ...props.address,
        [key]: value,
    });
};

const line1Model = computed({
    get: (): string => props.address.line1,
    set: (value: string): void => updateField("line1", value),
});

const cityModel = computed({
    get: (): string => props.address.city,
    set: (value: string): void => updateField("city", value),
});

const countryModel = computed({
    get: (): string => props.address.country,
    set: (value: string): void => updateField("country", value),
});

const postcodeModel = computed({
    get: (): string => props.address.postcode,
    set: (value: string): void => updateField("postcode", value),
});
</script>
