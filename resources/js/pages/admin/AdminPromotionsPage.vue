<template>
    <section class="grid" style="gap: 1rem">
        <div class="card">
            <h1 style="margin-top: 0">Admin promotions</h1>
            <form class="stack" @submit.prevent="createPromotion">
                <input v-model="form.name" placeholder="Name" required />
                <input v-model="form.code" placeholder="Code" />
                <select v-model="form.type">
                    <option value="percent">Percent</option>
                    <option value="fixed">Fixed</option>
                </select>
                <input v-model.number="form.value" type="number" min="0.01" step="0.01" required />
                <button class="btn btn-primary" type="submit">Create</button>
            </form>
        </div>

        <div class="card">
            <table class="table">
                <thead>
                <tr>
                    <th>Name</th>
                    <th>Code</th>
                    <th>Type</th>
                    <th>Value</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="promotion in promotions" :key="promotion.id">
                    <td>{{ promotion.name }}</td>
                    <td>{{ promotion.code }}</td>
                    <td>{{ promotion.type }}</td>
                    <td>{{ promotion.value }}</td>
                </tr>
                </tbody>
            </table>
        </div>
    </section>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue';

import { apiClient } from '@/api/client';

const promotions = ref<Array<{ id: number; name: string; code: string; type: string; value: number }>>([]);
const form = reactive({
    name: '',
    code: '',
    type: 'percent',
    value: 10,
});

const loadPromotions = async (): Promise<void> => {
    const { data } = await apiClient.get('/admin/promotions');
    promotions.value = data.data.data;
};

const createPromotion = async (): Promise<void> => {
    await apiClient.post('/admin/promotions', form);
    await loadPromotions();
};

onMounted(async () => {
    await loadPromotions();
});
</script>
