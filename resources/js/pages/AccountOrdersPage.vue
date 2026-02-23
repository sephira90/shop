<template>
    <section class="grid">
        <AccountTabsNav />

        <AppCard>
            <AccountOrdersHeaderCard :is-loading="isLoading" @refresh="loadOrders(page)" />

            <AccountOrdersMetricsRow
                :loaded-orders="orders.length"
                :paid-count="paidCount"
                :shipment-active-count="shipmentActiveCount"
                :loaded-total-label="formatPrice(loadedTotal)"
            />

            <AccountOrdersFiltersBar
                v-model:search-query="searchQuery"
                v-model:status-filter="statusFilter"
                :is-loading="isLoading"
                @apply="applyFilters"
            />

            <AppNotice v-if="loadError" class="actions--top" :message="loadError" />
        </AppCard>

        <AppEmptyState v-if="isLoading" in-card message="Loading orders..." />

        <div v-else-if="filteredOrders.length" class="grid">
            <AccountOrderCard
                v-for="order in filteredOrders"
                :key="order.id"
                :order="order"
                :expanded="isExpanded(order.id)"
                :total-items="totalItems"
                :format-price="formatPrice"
                :format-date="formatDate"
                :format-address="formatAddress"
                :order-status-tone="orderStatusTone"
                :payment-status-tone="paymentStatusTone"
                :shipment-status-tone="shipmentStatusTone"
                @toggle-details="toggleDetails"
            />
        </div>

        <AppEmptyState v-else in-card message="No orders found for current filters." />

        <AccountOrdersPaginationCard
            :page="page"
            :meta="meta"
            :is-loading="isLoading"
            @load-prev="loadOrders(page - 1)"
            @load-next="loadOrders(page + 1)"
        />
    </section>
</template>

<script setup lang="ts">
import AccountTabsNav from "@/components/account/AccountTabsNav.vue";
import AccountOrderCard from "@/components/account/orders/AccountOrderCard.vue";
import AccountOrdersFiltersBar from "@/components/account/orders/AccountOrdersFiltersBar.vue";
import AccountOrdersHeaderCard from "@/components/account/orders/AccountOrdersHeaderCard.vue";
import AccountOrdersMetricsRow from "@/components/account/orders/AccountOrdersMetricsRow.vue";
import AccountOrdersPaginationCard from "@/components/account/orders/AccountOrdersPaginationCard.vue";
import AppCard from "@/components/ui/layout/AppCard.vue";
import AppEmptyState from "@/components/ui/feedback/AppEmptyState.vue";
import AppNotice from "@/components/ui/feedback/AppNotice.vue";
import { useAccountOrders } from "@/composables/useAccountOrders";

const {
    orders,
    filteredOrders,
    searchQuery,
    statusFilter,
    page,
    meta,
    isLoading,
    loadError,
    loadedTotal,
    paidCount,
    shipmentActiveCount,
    loadOrders,
    applyFilters,
    isExpanded,
    toggleDetails,
    totalItems,
    formatPrice,
    formatDate,
    formatAddress,
    orderStatusTone,
    paymentStatusTone,
    shipmentStatusTone,
} = useAccountOrders();
</script>
