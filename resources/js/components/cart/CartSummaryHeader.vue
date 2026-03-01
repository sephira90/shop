<template>
    <AppStackBetween>
        <AppSectionTitle tag="h1">Cart</AppSectionTitle>
        <p class="order-total">Total: {{ formatPrice(total, currency) }}</p>
    </AppStackBetween>
</template>

<script setup lang="ts">
import AppSectionTitle from "@/components/ui/typography/AppSectionTitle.vue";
import AppStackBetween from "@/components/ui/actions/AppStackBetween.vue";
import { formatPrice } from "@/utils/format";

withDefaults(
    defineProps<{
        total: number;
        currency?: string;
    }>(),
    {
        currency: "USD",
    },
);
</script>
