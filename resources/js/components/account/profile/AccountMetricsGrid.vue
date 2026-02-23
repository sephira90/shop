<template>
    <div class="grid grid-4">
        <AppMetricCard label="Total orders" :value="metrics.totalOrders" in-card />
        <AppMetricCard label="Paid orders" :value="metrics.paidOrders" in-card />
        <AppMetricCard label="In delivery" :value="metrics.inDelivery" in-card />
        <AppMetricCard
            label="Spent (loaded)"
            :value="formatPrice(metrics.loadedTotalSpent)"
            in-card
        />
    </div>
</template>

<script setup lang="ts">
import AppMetricCard from "@/components/ui/AppMetricCard.vue";

interface AccountProfileMetrics {
    totalOrders: number;
    paidOrders: number;
    inDelivery: number;
    loadedTotalSpent: number;
}

defineProps<{
    metrics: AccountProfileMetrics;
    formatPrice: (value: number) => string;
}>();
</script>
