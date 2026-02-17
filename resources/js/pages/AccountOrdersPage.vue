<template>
    <section class="card">
        <h1 style="margin-top: 0">My orders</h1>
        <table class="table" v-if="orders.length">
            <thead>
            <tr>
                <th>Order</th>
                <th>Status</th>
                <th>Total</th>
                <th>Placed at</th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="order in orders" :key="order.id">
                <td>{{ order.order_number }}</td>
                <td>{{ order.status }}</td>
                <td>{{ order.total }}</td>
                <td>{{ order.placed_at }}</td>
            </tr>
            </tbody>
        </table>
        <p v-else>No orders yet.</p>
    </section>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue';

import { apiClient } from '@/api/client';

interface Order {
    id: string;
    order_number: string;
    status: string;
    total: number;
    placed_at: string;
}

const orders = ref<Order[]>([]);

onMounted(async () => {
    try {
        const { data } = await apiClient.get('/orders/me');
        orders.value = data.data;
    } catch {
        orders.value = [];
    }
});
</script>
