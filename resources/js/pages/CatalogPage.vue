<template>
    <section class="grid" style="gap: 1rem">
        <div class="card">
            <div class="stack">
                <input v-model="filters.q" placeholder="Search products" @keyup.enter="loadProducts" />
                <select v-model="filters.sort">
                    <option value="newest">Newest</option>
                    <option value="price_asc">Price ascending</option>
                    <option value="price_desc">Price descending</option>
                    <option value="name_asc">Name ascending</option>
                </select>
                <button class="btn btn-primary" type="button" @click="loadProducts">Apply</button>
            </div>
        </div>

        <div class="grid grid-3">
            <article v-for="product in products" :key="product.id" class="card">
                <h3 style="margin-top: 0">{{ product.name }}</h3>
                <p>{{ product.short_description }}</p>
                <p style="font-weight: 600">From {{ product.variants?.[0]?.price ?? 0 }} {{ product.variants?.[0]?.currency ?? 'USD' }}</p>
                <div class="stack">
                    <RouterLink class="btn btn-muted" :to="`/product/${product.slug}`">Open</RouterLink>
                    <button
                        v-if="product.variants?.[0]"
                        class="btn btn-primary"
                        type="button"
                        @click="addToCart(product.variants[0].id)"
                    >
                        Add to cart
                    </button>
                </div>
            </article>
        </div>
    </section>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue';
import { RouterLink } from 'vue-router';

import { apiClient } from '@/api/client';
import { useCartStore } from '@/stores/cart';

interface Product {
    id: number;
    name: string;
    slug: string;
    short_description: string;
    variants: Array<{ id: number; price: number; currency: string }>;
}

const cartStore = useCartStore();
const products = ref<Product[]>([]);
const filters = reactive({
    q: '',
    sort: 'newest',
});

const loadProducts = async (): Promise<void> => {
    const { data } = await apiClient.get('/catalog/products', {
        params: {
            q: filters.q,
            sort: filters.sort,
        },
    });

    products.value = data.data;
};

const addToCart = async (variantId: number): Promise<void> => {
    await cartStore.upsertItem(variantId, 1);
};

onMounted(async () => {
    await loadProducts();
});
</script>
