<template>
    <AppCard v-if="selectedOrderDetail || isDetailLoading">
        <AppEmptyState v-if="isDetailLoading" message="Loading order details..." />

        <template v-else-if="selectedOrderDetail">
            <AppStackBetween>
                <div>
                    <AppSectionTitle>
                        Order details: {{ selectedOrderDetail.order_number }}
                    </AppSectionTitle>
                    <AppMutedText>Customer: {{ selectedOrderDetail.email }}</AppMutedText>
                </div>
                <AppActionsRow>
                    <AppStatusStack
                        :items="[
                            {
                                key: 'order',
                                label: selectedOrderDetail.status,
                                tone: orderStatusTone(selectedOrderDetail.status),
                            },
                            {
                                key: 'payment',
                                label: selectedOrderDetail.payment_status,
                                tone: paymentStatusTone(selectedOrderDetail.payment_status),
                            },
                            {
                                key: 'shipment',
                                label: selectedOrderDetail.shipment_status,
                                tone: shipmentStatusTone(selectedOrderDetail.shipment_status),
                            },
                        ]"
                    />
                </AppActionsRow>
            </AppStackBetween>

            <AppGridThreeColumns with-top-spacing>
                <AppMetricCard
                    label="Total"
                    :value="formatPrice(selectedOrderDetail.total, selectedOrderDetail.currency)"
                    variant="soft"
                />
                <AppMetricCard
                    label="Subtotal"
                    :value="formatPrice(selectedOrderDetail.subtotal, selectedOrderDetail.currency)"
                    variant="soft"
                />
                <AppMetricCard
                    label="Items"
                    :value="selectedOrderDetail.items.length"
                    variant="soft"
                />
            </AppGridThreeColumns>

            <AdminOrderStatusEditor v-model:draft="currentDraft" @save="$emit('saveStatuses')" />

            <AppGridTwoColumns with-top-spacing>
                <AppDetailBox
                    title="Billing address"
                    :content="formatAddress(selectedOrderDetail.billing_address)"
                />
                <AppDetailBox
                    title="Shipping address"
                    :content="formatAddress(selectedOrderDetail.shipping_address)"
                />
            </AppGridTwoColumns>

            <OrderItemsTable
                :items="selectedOrderDetail.items"
                :format-price="formatPrice"
                :currency="selectedOrderDetail.currency"
                :item-key-prefix="selectedOrderDetail.id"
                with-top-spacing
            />
        </template>
    </AppCard>
</template>

<script setup lang="ts">
import type { StatusDraft } from "@/composables/admin/orders/useAdminOrdersQuery";
import type { AddressPayload, AdminOrderDetail } from "@/types/admin-orders";
import type { StatusTone } from "@/utils/order-presentation";

import OrderItemsTable from "@/components/orders/OrderItemsTable.vue";
import AppActionsRow from "@/components/ui/AppActionsRow.vue";
import AppCard from "@/components/ui/AppCard.vue";
import AppDetailBox from "@/components/ui/AppDetailBox.vue";
import AppEmptyState from "@/components/ui/AppEmptyState.vue";
import AppGridTwoColumns from "@/components/ui/AppGridTwoColumns.vue";
import AppGridThreeColumns from "@/components/ui/AppGridThreeColumns.vue";
import AppMetricCard from "@/components/ui/AppMetricCard.vue";
import AppMutedText from "@/components/ui/AppMutedText.vue";
import AppSectionTitle from "@/components/ui/AppSectionTitle.vue";
import AppStackBetween from "@/components/ui/AppStackBetween.vue";
import AppStatusStack from "@/components/ui/AppStatusStack.vue";
import AdminOrderStatusEditor from "./AdminOrderStatusEditor.vue";

defineProps<{
    selectedOrderDetail: AdminOrderDetail | null;
    isDetailLoading: boolean;
    orderStatusTone: (status: string) => StatusTone;
    paymentStatusTone: (status: string) => StatusTone;
    shipmentStatusTone: (status: string) => StatusTone;
    formatPrice: (value: number, currency?: string) => string;
    formatAddress: (address: AddressPayload | null) => string;
}>();

defineEmits<{
    (event: "saveStatuses"): void;
}>();

const currentDraft = defineModel<StatusDraft>("currentDraft", { required: true });
</script>
