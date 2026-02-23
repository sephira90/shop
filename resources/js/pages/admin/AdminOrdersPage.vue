<template>
    <section class="grid">
        <AppCard>
            <AdminOrdersHeaderCard :is-loading="isLoading" @refresh="loadOrders(page)" />

            <AdminOrdersMetricsRow
                :loaded-count="orders.length"
                :paid-count="paidCount"
                :completed-count="completedCount"
                :pending-payment-count="pendingPaymentCount"
            />

            <AdminOrdersFiltersBar v-model:filters="filters" :is-loading="isLoading" />

            <AppNotice
                v-if="notice.message"
                :message="notice.message"
                :variant="notice.type === 'success' ? 'success' : 'error'"
            />

            <AdminOrdersTableCard
                :orders="filteredOrders"
                :is-loading="isLoading"
                :selected-order-id="selectedOrderId"
                :order-status-tone="orderStatusTone"
                :payment-status-tone="paymentStatusTone"
                :shipment-status-tone="shipmentStatusTone"
                :format-price="formatPrice"
                @select="selectOrder"
            />

            <AdminOrdersPaginationBar
                :page="page"
                :meta="meta"
                :is-loading="isLoading"
                @load-prev="loadOrders(page - 1)"
                @load-next="loadOrders(page + 1)"
            />
        </AppCard>

        <AdminOrderDetailsPanel
            v-model:current-draft="currentDraft"
            :selected-order-detail="selectedOrderDetail"
            :is-detail-loading="isDetailLoading"
            :order-status-tone="orderStatusTone"
            :payment-status-tone="paymentStatusTone"
            :shipment-status-tone="shipmentStatusTone"
            :format-price="formatPrice"
            :format-address="formatAddress"
            @save-statuses="updateSelectedOrderStatus"
        />
    </section>
</template>

<script setup lang="ts">
import { onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";

import AdminOrderDetailsPanel from "@/components/admin/orders/AdminOrderDetailsPanel.vue";
import AdminOrdersFiltersBar from "@/components/admin/orders/AdminOrdersFiltersBar.vue";
import AdminOrdersHeaderCard from "@/components/admin/orders/AdminOrdersHeaderCard.vue";
import AdminOrdersMetricsRow from "@/components/admin/orders/AdminOrdersMetricsRow.vue";
import AdminOrdersPaginationBar from "@/components/admin/orders/AdminOrdersPaginationBar.vue";
import AdminOrdersTableCard from "@/components/admin/orders/AdminOrdersTableCard.vue";
import AppCard from "@/components/ui/layout/AppCard.vue";
import AppNotice from "@/components/ui/feedback/AppNotice.vue";
import { useAdminOrders } from "@/composables/admin/useAdminOrders";

const route = useRoute();
const router = useRouter();

const {
    orders,
    selectedOrderId,
    isLoading,
    isDetailLoading,
    page,
    meta,
    filters,
    notice,
    filteredOrders,
    selectedOrderDetail,
    currentDraft,
    paidCount,
    completedCount,
    pendingPaymentCount,
    loadOrders,
    selectOrder,
    updateSelectedOrderStatus,
    formatPrice,
    formatAddress,
    orderStatusTone,
    paymentStatusTone,
    shipmentStatusTone,
} = useAdminOrders({
    routeSync: {
        route,
        router,
    },
});

onMounted(async () => {
    await loadOrders();
});
</script>
