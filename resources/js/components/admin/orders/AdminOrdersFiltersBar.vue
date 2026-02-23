<template>
    <AppActionsRow with-top-spacing>
        <AppSearchInput
            v-model="filters.search"
            placeholder="Search by order number or email"
            :disabled="isLoading"
        />
        <AppFilterSelect v-model="filters.orderStatus" :disabled="isLoading">
            <option value="all">All order statuses</option>
            <option value="pending">Pending</option>
            <option value="paid">Paid</option>
            <option value="processing">Processing</option>
            <option value="shipped">Shipped</option>
            <option value="completed">Completed</option>
            <option value="cancelled">Cancelled</option>
            <option value="refunded">Refunded</option>
        </AppFilterSelect>
        <AppFilterSelect v-model="filters.paymentStatus" :disabled="isLoading">
            <option value="all">All payment statuses</option>
            <option value="pending">Pending</option>
            <option value="authorized">Authorized</option>
            <option value="captured">Captured</option>
            <option value="failed">Failed</option>
            <option value="refunded">Refunded</option>
        </AppFilterSelect>
        <AppFilterSelect v-model="filters.shipmentStatus" :disabled="isLoading">
            <option value="all">All shipment statuses</option>
            <option value="pending">Pending</option>
            <option value="packed">Packed</option>
            <option value="shipped">Shipped</option>
            <option value="delivered">Delivered</option>
            <option value="returned">Returned</option>
        </AppFilterSelect>
    </AppActionsRow>
</template>

<script setup lang="ts">
import AppActionsRow from "@/components/ui/actions/AppActionsRow.vue";
import AppFilterSelect from "@/components/ui/forms/AppFilterSelect.vue";
import AppSearchInput from "@/components/ui/forms/AppSearchInput.vue";
import type { AdminOrderFilters } from "@/queries/admin/orders";

defineProps<{
    isLoading: boolean;
}>();

const filters = defineModel<AdminOrderFilters>("filters", { required: true });
</script>
