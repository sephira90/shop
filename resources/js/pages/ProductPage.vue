<template>
    <section v-if="product" class="card">
        <h1 style="margin-top: 0">{{ product.name }}</h1>
        <p>{{ product.description }}</p>
        <div class="stack">
            <select v-model.number="selectedVariantId">
                <option v-for="variant in product.variants" :key="variant.id" :value="variant.id">
                    {{ variant.name }} - {{ variant.price }} {{ variant.currency }}
                </option>
            </select>
            <button class="btn btn-primary" type="button" @click="addToCart">Add to cart</button>
        </div>
    </section>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';

import { apiClient } from '@/api/client';
import { useCartStore } from '@/stores/cart';

interface Product {
    id: number;
    name: string;
    slug: string;
    description: string;
    variants: Array<{ id: number; name: string; price: number; currency: string }>;
}

const route = useRoute();
const cartStore = useCartStore();
const product = ref<Product | null>(null);
const selectedVariantId = ref<number | null>(null);

const loadProduct = async (): Promise<void> => {
    const { data } = await apiClient.get(`/catalog/products/${route.params.slug as string}`);
    const loadedProduct = data.data as Product;
    product.value = loadedProduct;
    selectedVariantId.value = loadedProduct.variants[0]?.id ?? null;
};

const addToCart = async (): Promise<void> => {
    if (!selectedVariantId.value) {
        return;
    }

    await cartStore.upsertItem(selectedVariantId.value, 1);
};

onMounted(async () => {
    await loadProduct();
});
</script>
