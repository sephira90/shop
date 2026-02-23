<template>
    <div class="grid grid-4 actions--top">
        <AppMetricCard label="Loaded orders" :value="loadedCount" variant="soft" />
        <AppMetricCard label="Paid" :value="paidCount" variant="soft" />
        <AppMetricCard label="Completed" :value="completedCount" variant="soft" />
        <AppMetricCard label="Pending payment" :value="pendingPaymentCount" variant="soft" />
    </div>
</template>

<script setup lang="ts">
import AppMetricCard from "@/components/ui/data-display/AppMetricCard.vue";

defineProps<{
    loadedCount: number;
    paidCount: number;
    completedCount: number;
    pendingPaymentCount: number;
}>();
</script>
