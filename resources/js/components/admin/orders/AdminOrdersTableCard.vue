<template>
    <AppTableSection with-top-spacing>
        <thead>
            <tr>
                <th>Order</th>
                <th>Customer</th>
                <th>Statuses</th>
                <th>Total</th>
                <th>Placed</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody v-if="orders.length">
            <tr
                v-for="order in orders"
                :key="order.id"
                :class="{ 'table-row-active': selectedOrderId === order.id }"
            >
                <td>
                    <strong>{{ order.order_number }}</strong>
                    <AppMutedText>{{ order.id }}</AppMutedText>
                </td>
                <td>{{ order.email }}</td>
                <td>
                    <AppStatusStack
                        :items="[
                            {
                                key: `${order.id}-order`,
                                label: order.status,
                                tone: orderStatusTone(order.status),
                            },
                            {
                                key: `${order.id}-payment`,
                                label: order.payment_status,
                                tone: paymentStatusTone(order.payment_status),
                            },
                            {
                                key: `${order.id}-shipment`,
                                label: order.shipment_status,
                                tone: shipmentStatusTone(order.shipment_status),
                            },
                        ]"
                    />
                </td>
                <td>{{ formatPrice(order.total, order.currency) }}</td>
                <td>
                    {{
                        formatDateTime(order.placed_at ?? order.created_at, {
                            fallback: "Unknown date",
                        })
                    }}
                </td>
                <AppTableActionsCell>
                    <AppButton variant="muted" type="button" @click="$emit('select', order.id)">
                        Details
                    </AppButton>
                </AppTableActionsCell>
            </tr>
        </tbody>
        <tbody v-else>
            <AppTableEmptyStateRow
                :colspan="6"
                :message="isLoading ? 'Loading orders...' : 'No orders match current filters.'"
            />
        </tbody>
    </AppTableSection>
</template>

<script setup lang="ts">
import AppButton from "@/components/ui/AppButton.vue";
import AppMutedText from "@/components/ui/AppMutedText.vue";
import AppTableSection from "@/components/ui/AppTableSection.vue";
import AppTableActionsCell from "@/components/ui/AppTableActionsCell.vue";
import AppTableEmptyStateRow from "@/components/ui/AppTableEmptyStateRow.vue";
import AppStatusStack from "@/components/ui/AppStatusStack.vue";
import type { AdminOrderSummary } from "@/types/admin-orders";
import type { StatusTone } from "@/utils/order-presentation";
import { formatDateTime } from "@/utils/datetime";

defineProps<{
    orders: AdminOrderSummary[];
    isLoading: boolean;
    selectedOrderId: string | null;
    orderStatusTone: (status: string) => StatusTone;
    paymentStatusTone: (status: string) => StatusTone;
    shipmentStatusTone: (status: string) => StatusTone;
    formatPrice: (value: number, currency?: string) => string;
}>();

defineEmits<{
    (event: "select", orderId: string): void;
}>();
</script>
