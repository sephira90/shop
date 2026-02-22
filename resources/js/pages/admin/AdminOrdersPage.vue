<template>
    <section class="grid">
        <div class="card">
            <div class="stack stack--between">
                <div>
                    <h1 class="section-title">Admin orders</h1>
                    <p class="muted">
                        Monitor order lifecycle and update statuses from one workspace.
                    </p>
                </div>
                <button
                    class="btn btn-muted"
                    type="button"
                    :disabled="isLoading"
                    @click="loadOrders(page)"
                >
                    {{ isLoading ? "Refreshing..." : "Refresh list" }}
                </button>
            </div>

            <div class="grid grid-4 actions--top">
                <article class="metric-card metric-card--soft">
                    <span class="metric-card__label">Loaded orders</span>
                    <strong class="metric-card__value">{{ orders.length }}</strong>
                </article>
                <article class="metric-card metric-card--soft">
                    <span class="metric-card__label">Paid</span>
                    <strong class="metric-card__value">{{ paidCount }}</strong>
                </article>
                <article class="metric-card metric-card--soft">
                    <span class="metric-card__label">Completed</span>
                    <strong class="metric-card__value">{{ completedCount }}</strong>
                </article>
                <article class="metric-card metric-card--soft">
                    <span class="metric-card__label">Pending payment</span>
                    <strong class="metric-card__value">{{ pendingPaymentCount }}</strong>
                </article>
            </div>

            <div class="actions actions--top">
                <input
                    v-model="filters.search"
                    placeholder="Search by order number or email"
                    :disabled="isLoading"
                />
                <select v-model="filters.orderStatus" :disabled="isLoading">
                    <option value="all">All order statuses</option>
                    <option value="pending">Pending</option>
                    <option value="paid">Paid</option>
                    <option value="processing">Processing</option>
                    <option value="shipped">Shipped</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                    <option value="refunded">Refunded</option>
                </select>
                <select v-model="filters.paymentStatus" :disabled="isLoading">
                    <option value="all">All payment statuses</option>
                    <option value="pending">Pending</option>
                    <option value="authorized">Authorized</option>
                    <option value="captured">Captured</option>
                    <option value="failed">Failed</option>
                    <option value="refunded">Refunded</option>
                </select>
                <select v-model="filters.shipmentStatus" :disabled="isLoading">
                    <option value="all">All shipment statuses</option>
                    <option value="pending">Pending</option>
                    <option value="packed">Packed</option>
                    <option value="shipped">Shipped</option>
                    <option value="delivered">Delivered</option>
                    <option value="returned">Returned</option>
                </select>
            </div>

            <p
                v-if="notice.message"
                :class="['notice', notice.type === 'success' ? 'notice--success' : 'notice--error']"
            >
                {{ notice.message }}
            </p>

            <div class="table-wrap actions--top">
                <table class="table">
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
                    <tbody v-if="filteredOrders.length">
                        <tr
                            v-for="order in filteredOrders"
                            :key="order.id"
                            :class="{ 'table-row-active': selectedOrderId === order.id }"
                        >
                            <td>
                                <strong>{{ order.order_number }}</strong>
                                <p class="muted">{{ order.id }}</p>
                            </td>
                            <td>{{ order.email }}</td>
                            <td>
                                <div class="status-stack">
                                    <span
                                        class="status-chip"
                                        :class="orderStatusClass(order.status)"
                                        >{{ order.status }}</span
                                    >
                                    <span
                                        class="status-chip"
                                        :class="paymentStatusClass(order.payment_status)"
                                        >{{ order.payment_status }}</span
                                    >
                                    <span
                                        class="status-chip"
                                        :class="shipmentStatusClass(order.shipment_status)"
                                        >{{ order.shipment_status }}</span
                                    >
                                </div>
                            </td>
                            <td>{{ formatPrice(order.total, order.currency) }}</td>
                            <td>
                                {{
                                    formatDateTime(order.placed_at ?? order.created_at, {
                                        fallback: "Unknown date",
                                    })
                                }}
                            </td>
                            <td>
                                <button
                                    class="btn btn-muted"
                                    type="button"
                                    @click="selectOrder(order.id)"
                                >
                                    Details
                                </button>
                            </td>
                        </tr>
                    </tbody>
                    <tbody v-else>
                        <tr>
                            <td colspan="6">
                                <div class="empty-state">
                                    <p>
                                        {{
                                            isLoading
                                                ? "Loading orders..."
                                                : "No orders match current filters."
                                        }}
                                    </p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="stack stack--between actions--top">
                <p class="muted">
                    Page {{ meta.current_page }} of {{ meta.last_page }}. Total orders:
                    {{ meta.total }}.
                </p>
                <div class="actions">
                    <button
                        class="btn btn-muted"
                        type="button"
                        :disabled="page <= 1 || isLoading"
                        @click="loadOrders(page - 1)"
                    >
                        Previous
                    </button>
                    <button
                        class="btn btn-muted"
                        type="button"
                        :disabled="page >= meta.last_page || isLoading"
                        @click="loadOrders(page + 1)"
                    >
                        Next
                    </button>
                </div>
            </div>
        </div>

        <div class="card" v-if="selectedOrderDetail || isDetailLoading">
            <div v-if="isDetailLoading" class="empty-state">
                <p>Loading order details...</p>
            </div>

            <template v-else-if="selectedOrderDetail">
                <div class="stack stack--between">
                    <div>
                        <h2 class="section-title">
                            Order details: {{ selectedOrderDetail.order_number }}
                        </h2>
                        <p class="muted">Customer: {{ selectedOrderDetail.email }}</p>
                    </div>
                    <div class="actions">
                        <span
                            class="status-chip"
                            :class="orderStatusClass(selectedOrderDetail.status)"
                            >{{ selectedOrderDetail.status }}</span
                        >
                        <span
                            class="status-chip"
                            :class="paymentStatusClass(selectedOrderDetail.payment_status)"
                        >
                            {{ selectedOrderDetail.payment_status }}
                        </span>
                        <span
                            class="status-chip"
                            :class="shipmentStatusClass(selectedOrderDetail.shipment_status)"
                        >
                            {{ selectedOrderDetail.shipment_status }}
                        </span>
                    </div>
                </div>

                <div class="grid grid-3 actions--top">
                    <article class="metric-card metric-card--soft">
                        <span class="metric-card__label">Total</span>
                        <strong class="metric-card__value">{{
                            formatPrice(selectedOrderDetail.total, selectedOrderDetail.currency)
                        }}</strong>
                    </article>
                    <article class="metric-card metric-card--soft">
                        <span class="metric-card__label">Subtotal</span>
                        <strong class="metric-card__value">{{
                            formatPrice(selectedOrderDetail.subtotal, selectedOrderDetail.currency)
                        }}</strong>
                    </article>
                    <article class="metric-card metric-card--soft">
                        <span class="metric-card__label">Items</span>
                        <strong class="metric-card__value">{{
                            selectedOrderDetail.items.length
                        }}</strong>
                    </article>
                </div>

                <div class="grid grid-3 actions--top">
                    <label class="field">
                        <span class="field__label">Order status</span>
                        <select v-model="currentDraft.status">
                            <option value="pending">Pending</option>
                            <option value="paid">Paid</option>
                            <option value="processing">Processing</option>
                            <option value="shipped">Shipped</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                            <option value="refunded">Refunded</option>
                        </select>
                    </label>
                    <label class="field">
                        <span class="field__label">Payment status</span>
                        <select v-model="currentDraft.payment_status">
                            <option value="pending">Pending</option>
                            <option value="authorized">Authorized</option>
                            <option value="captured">Captured</option>
                            <option value="failed">Failed</option>
                            <option value="refunded">Refunded</option>
                        </select>
                    </label>
                    <label class="field">
                        <span class="field__label">Shipment status</span>
                        <select v-model="currentDraft.shipment_status">
                            <option value="pending">Pending</option>
                            <option value="packed">Packed</option>
                            <option value="shipped">Shipped</option>
                            <option value="delivered">Delivered</option>
                            <option value="returned">Returned</option>
                        </select>
                    </label>
                </div>

                <div class="actions actions--top">
                    <button
                        class="btn btn-primary"
                        type="button"
                        :disabled="currentDraft.saving"
                        @click="updateSelectedOrderStatus"
                    >
                        {{ currentDraft.saving ? "Saving..." : "Save statuses" }}
                    </button>
                </div>

                <div class="grid grid-2 actions--top">
                    <article class="order-detail-box">
                        <h3>Billing address</h3>
                        <p class="muted">
                            {{ formatAddress(selectedOrderDetail.billing_address) }}
                        </p>
                    </article>
                    <article class="order-detail-box">
                        <h3>Shipping address</h3>
                        <p class="muted">
                            {{ formatAddress(selectedOrderDetail.shipping_address) }}
                        </p>
                    </article>
                </div>

                <div class="table-wrap actions--top">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Qty</th>
                                <th>Unit</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="item in selectedOrderDetail.items"
                                :key="`${selectedOrderDetail.id}-${item.sku}`"
                            >
                                <td>
                                    <strong>{{ item.name }}</strong>
                                    <p class="muted">{{ item.sku }}</p>
                                </td>
                                <td>{{ item.quantity }}</td>
                                <td>
                                    {{ formatPrice(item.unit_price, selectedOrderDetail.currency) }}
                                </td>
                                <td>
                                    {{
                                        formatPrice(item.total_price, selectedOrderDetail.currency)
                                    }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </template>
        </div>
    </section>
</template>

<script setup lang="ts">
import { onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";

import { useAdminOrders } from "@/composables/admin/useAdminOrders";
import { formatDateTime } from "@/utils/datetime";

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
    orderStatusClass,
    paymentStatusClass,
    shipmentStatusClass,
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
