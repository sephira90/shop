<template>
    <AppCard tag="article" class="order-card">
        <AppStackBetween>
            <div>
                <h2 class="order-card__title">{{ order.order_number }}</h2>
                <AppMutedText>{{ formatDate(order.placed_at ?? order.created_at) }}</AppMutedText>
            </div>
            <AppActionsRow>
                <AppStatusStack
                    :items="[
                        {
                            key: 'order',
                            label: `Order: ${order.status}`,
                            tone: orderStatusTone(order.status),
                        },
                        {
                            key: 'payment',
                            label: `Payment: ${order.payment_status}`,
                            tone: paymentStatusTone(order.payment_status),
                        },
                        {
                            key: 'shipment',
                            label: `Shipment: ${order.shipment_status}`,
                            tone: shipmentStatusTone(order.shipment_status),
                        },
                    ]"
                />
            </AppActionsRow>
        </AppStackBetween>

        <div class="order-card__summary">
            <div>
                <AppMutedText tag="span">Total</AppMutedText>
                <strong>{{ formatPrice(order.total, order.currency) }}</strong>
            </div>
            <div>
                <AppMutedText tag="span">Items</AppMutedText>
                <strong>{{
                    detail
                        ? totalItems(detail)
                        : expanded && isDetailLoading
                          ? "..."
                          : "Open details"
                }}</strong>
            </div>
            <div>
                <AppMutedText tag="span">Email</AppMutedText>
                <strong>{{ order.email }}</strong>
            </div>
        </div>

        <AppActionsRow>
            <AppButton variant="muted" type="button" @click="$emit('toggleDetails', order.id)">
                {{ expanded ? "Hide details" : "Show details" }}
            </AppButton>
        </AppActionsRow>

        <div v-if="expanded" class="order-card__details">
            <AppNotice v-if="detailError" class="actions--top" :message="detailError" />
            <AppMutedText v-else-if="isDetailLoading">Loading details...</AppMutedText>
            <AccountOrderDetailsTable
                v-else-if="detail"
                :order="detail"
                :format-price="formatPrice"
                :format-address="formatAddress"
            />
        </div>
    </AppCard>
</template>

<script setup lang="ts">
import AppActionsRow from "@/components/ui/actions/AppActionsRow.vue";
import AppButton from "@/components/ui/actions/AppButton.vue";
import AppCard from "@/components/ui/layout/AppCard.vue";
import AppNotice from "@/components/ui/feedback/AppNotice.vue";
import AppMutedText from "@/components/ui/typography/AppMutedText.vue";
import AppStackBetween from "@/components/ui/actions/AppStackBetween.vue";
import AppStatusStack from "@/components/ui/data-display/AppStatusStack.vue";
import type {
    AccountOrderAddress,
    AccountOrderDetail,
    AccountOrderSummary,
} from "@/types/account-orders";
import type { StatusTone } from "@/utils/order-presentation";

import AccountOrderDetailsTable from "./AccountOrderDetailsTable.vue";

defineProps<{
    order: AccountOrderSummary;
    detail: AccountOrderDetail | null;
    expanded: boolean;
    isDetailLoading: boolean;
    detailError: string;
    totalItems: (order: AccountOrderDetail | null) => number;
    formatPrice: (value: number, currency?: string) => string;
    formatDate: (value: string | null) => string;
    formatAddress: (address: AccountOrderAddress | null) => string;
    orderStatusTone: (status: string) => StatusTone;
    paymentStatusTone: (status: string) => StatusTone;
    shipmentStatusTone: (status: string) => StatusTone;
}>();

defineEmits<{
    (event: "toggleDetails", orderId: string): void;
}>();
</script>
