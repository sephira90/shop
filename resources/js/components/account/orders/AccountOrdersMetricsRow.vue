<template>
    <div class="grid grid-4 actions--top">
        <AppMetricCard label="Loaded orders" :value="loadedOrders" variant="soft" />
        <AppMetricCard label="Paid" :value="paidCount" variant="soft" />
        <AppMetricCard label="In shipment" :value="shipmentActiveCount" variant="soft" />
        <AppMetricCard label="Loaded value" :value="loadedTotalLabel" variant="soft" />
    </div>
</template>

<script setup lang="ts">
import AppMetricCard from "@/components/ui/AppMetricCard.vue";

defineProps<{
    loadedOrders: number;
    paidCount: number;
    shipmentActiveCount: number;
    loadedTotalLabel: string;
}>();
</script>
