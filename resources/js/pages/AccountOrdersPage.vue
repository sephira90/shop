<template>
    <section class="grid">
        <nav class="account-tabs" aria-label="Account navigation">
            <RouterLink to="/account/profile" class="account-tab">Profile</RouterLink>
            <RouterLink to="/account/orders" class="account-tab">Orders</RouterLink>
        </nav>

        <div class="card">
            <div class="stack stack--between">
                <div>
                    <h1 class="section-title">My orders</h1>
                    <p class="muted">
                        Track statuses, payment and shipment updates for your purchases.
                    </p>
                </div>
                <button
                    class="btn btn-muted"
                    type="button"
                    :disabled="isLoading"
                    @click="loadOrders(page)"
                >
                    {{ isLoading ? "Refreshing..." : "Refresh" }}
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
                    <span class="metric-card__label">In shipment</span>
                    <strong class="metric-card__value">{{ shipmentActiveCount }}</strong>
                </article>
                <article class="metric-card metric-card--soft">
                    <span class="metric-card__label">Loaded value</span>
                    <strong class="metric-card__value">{{ formatPrice(loadedTotal) }}</strong>
                </article>
            </div>

            <div class="actions actions--top">
                <input
                    v-model="searchQuery"
                    placeholder="Search by order number or email"
                    :disabled="isLoading"
                    @keyup.enter="applyFilters"
                />
                <select v-model="statusFilter" :disabled="isLoading" @change="applyFilters">
                    <option value="all">All statuses</option>
                    <option value="pending">Pending</option>
                    <option value="paid">Paid</option>
                    <option value="processing">Processing</option>
                    <option value="shipped">Shipped</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                    <option value="refunded">Refunded</option>
                </select>
            </div>

            <p v-if="loadError" class="notice notice--error actions--top">{{ loadError }}</p>
        </div>

        <div v-if="isLoading" class="card empty-state">
            <p>Loading orders...</p>
        </div>

        <div v-else-if="filteredOrders.length" class="grid">
            <article v-for="order in filteredOrders" :key="order.id" class="card order-card">
                <div class="stack stack--between">
                    <div>
                        <h2 class="order-card__title">{{ order.order_number }}</h2>
                        <p class="muted">{{ formatDate(order.placed_at ?? order.created_at) }}</p>
                    </div>
                    <div class="actions">
                        <span class="status-chip" :class="orderStatusClass(order.status)"
                            >Order: {{ order.status }}</span
                        >
                        <span class="status-chip" :class="paymentStatusClass(order.payment_status)">
                            Payment: {{ order.payment_status }}
                        </span>
                        <span
                            class="status-chip"
                            :class="shipmentStatusClass(order.shipment_status)"
                        >
                            Shipment: {{ order.shipment_status }}
                        </span>
                    </div>
                </div>

                <div class="order-card__summary">
                    <div>
                        <span class="muted">Total</span>
                        <strong>{{ formatPrice(order.total, order.currency) }}</strong>
                    </div>
                    <div>
                        <span class="muted">Items</span>
                        <strong>{{ totalItems(order) }}</strong>
                    </div>
                    <div>
                        <span class="muted">Email</span>
                        <strong>{{ order.email }}</strong>
                    </div>
                </div>

                <div class="actions">
                    <button class="btn btn-muted" type="button" @click="toggleDetails(order.id)">
                        {{ isExpanded(order.id) ? "Hide details" : "Show details" }}
                    </button>
                </div>

                <div v-if="isExpanded(order.id)" class="order-card__details">
                    <div class="grid grid-2">
                        <article class="order-detail-box">
                            <h3>Billing address</h3>
                            <p class="muted">{{ formatAddress(order.billing_address) }}</p>
                        </article>
                        <article class="order-detail-box">
                            <h3>Shipping address</h3>
                            <p class="muted">{{ formatAddress(order.shipping_address) }}</p>
                        </article>
                    </div>

                    <div class="table-wrap">
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
                                <tr v-for="item in order.items" :key="`${order.id}-${item.sku}`">
                                    <td>
                                        <strong>{{ item.name }}</strong>
                                        <p class="muted">{{ item.sku }}</p>
                                    </td>
                                    <td>{{ item.quantity }}</td>
                                    <td>{{ formatPrice(item.unit_price, order.currency) }}</td>
                                    <td>{{ formatPrice(item.total_price, order.currency) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </article>
        </div>

        <div v-else class="card empty-state">
            <p>No orders found for current filters.</p>
        </div>

        <div class="card">
            <div class="stack stack--between">
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
    </section>
</template>

<script setup lang="ts">
import { RouterLink } from "vue-router";

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
    orderStatusClass,
    paymentStatusClass,
    shipmentStatusClass,
} = useAccountOrders();
</script>
