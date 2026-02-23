<template>
    <AppGridTwoColumns>
        <AppDetailBox title="Billing address" :content="formatAddress(order.billing_address)" />
        <AppDetailBox title="Shipping address" :content="formatAddress(order.shipping_address)" />
    </AppGridTwoColumns>

    <OrderItemsTable
        :items="order.items"
        :format-price="formatPrice"
        :currency="order.currency"
        :item-key-prefix="order.id"
    />
</template>

<script setup lang="ts">
import OrderItemsTable from "@/components/orders/OrderItemsTable.vue";
import AppDetailBox from "@/components/ui/data-display/AppDetailBox.vue";
import AppGridTwoColumns from "@/components/ui/layout/AppGridTwoColumns.vue";
import type { AccountOrder, AccountOrderAddress } from "@/types/account-orders";

defineProps<{
    order: AccountOrder;
    formatPrice: (value: number, currency?: string) => string;
    formatAddress: (address: AccountOrderAddress | null) => string;
}>();
</script>
