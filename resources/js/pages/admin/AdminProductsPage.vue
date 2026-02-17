<template>
    <section class="grid" style="gap: 1rem">
        <div class="card">
            <h1 style="margin-top: 0">Admin products</h1>
            <table class="table">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>SKU</th>
                    <th>Status</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="product in products" :key="product.id">
                    <td>{{ product.id }}</td>
                    <td>{{ product.name }}</td>
                    <td>{{ product.sku }}</td>
                    <td>{{ product.status }}</td>
                </tr>
                </tbody>
            </table>
        </div>
    </section>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue';

import { apiClient } from '@/api/client';

const products = ref<Array<{ id: number; name: string; sku: string; status: string }>>([]);

onMounted(async () => {
    const { data } = await apiClient.get('/admin/products');
    products.value = data.data;
});
</script>
