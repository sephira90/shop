<template>
    <AppActionsRow with-top-spacing>
        <AppSearchInput
            v-model="searchQuery"
            placeholder="Search by order number or email"
            :disabled="isLoading"
            @enter="$emit('apply')"
        />
        <AppFilterSelect v-model="statusFilter" :disabled="isLoading" @change="$emit('apply')">
            <option value="all">All statuses</option>
            <option value="pending">Pending</option>
            <option value="paid">Paid</option>
            <option value="processing">Processing</option>
            <option value="shipped">Shipped</option>
            <option value="completed">Completed</option>
            <option value="cancelled">Cancelled</option>
            <option value="refunded">Refunded</option>
        </AppFilterSelect>
    </AppActionsRow>
</template>

<script setup lang="ts">
import AppActionsRow from "@/components/ui/AppActionsRow.vue";
import AppFilterSelect from "@/components/ui/AppFilterSelect.vue";
import AppSearchInput from "@/components/ui/AppSearchInput.vue";
import type { AccountOrderStatusFilter } from "@/types/account-orders";

defineProps<{
    isLoading: boolean;
}>();

defineEmits<{
    (event: "apply"): void;
}>();

const searchQuery = defineModel<string>("searchQuery", { required: true });
const statusFilter = defineModel<AccountOrderStatusFilter>("statusFilter", { required: true });
</script>
