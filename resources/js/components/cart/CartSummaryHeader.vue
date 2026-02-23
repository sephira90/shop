<template>
    <AppStackBetween>
        <AppSectionTitle tag="h1">Cart</AppSectionTitle>
        <p class="order-total">Total: {{ formatPrice(total) }}</p>
    </AppStackBetween>
</template>

<script setup lang="ts">
import AppSectionTitle from "@/components/ui/AppSectionTitle.vue";
import AppStackBetween from "@/components/ui/AppStackBetween.vue";

defineProps<{
    total: number;
}>();

const formatPrice = (value: number): string => {
    return Number(value).toFixed(2);
};
</script>
