<template>
    <section class="card">
        <h1 style="margin-top: 0">Admin orders</h1>
        <table class="table">
            <thead>
            <tr>
                <th>Order</th>
                <th>Status</th>
                <th>Payment</th>
                <th>Shipment</th>
                <th>Total</th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="order in orders" :key="order.id">
                <td>{{ order.order_number }}</td>
                <td>{{ order.status }}</td>
                <td>{{ order.payment_status }}</td>
                <td>{{ order.shipment_status }}</td>
                <td>{{ order.total }}</td>
            </tr>
            </tbody>
        </table>
    </section>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue';

import { apiClient } from '@/api/client';

const orders = ref<Array<{ id: string; order_number: string; status: string; payment_status: string; shipment_status: string; total: number }>>([]);

onMounted(async () => {
    const { data } = await apiClient.get('/admin/orders');
    orders.value = data.data;
});
</script>
