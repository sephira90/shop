<template>
    <section class="grid">
        <div class="card">
            <h1 class="section-title">Catalog</h1>
            <p class="muted">Search and sort products available in the storefront.</p>
            <div class="actions actions--top">
                <input
                    v-model="filters.q"
                    placeholder="Search products"
                    :disabled="isLoading"
                    @keyup.enter="applyFilters"
                />
                <select v-model="filters.sort">
                    <option value="newest">Newest</option>
                    <option value="price_asc">Price ascending</option>
                    <option value="price_desc">Price descending</option>
                    <option value="name_asc">Name ascending</option>
                </select>
                <button
                    class="btn btn-primary"
                    type="button"
                    :disabled="isLoading"
                    @click="applyFilters"
                >
                    {{ isLoading ? "Loading..." : "Apply filters" }}
                </button>
            </div>
            <p v-if="loadError" class="notice notice--error actions--top">{{ loadError }}</p>
        </div>

        <div v-if="isLoading && products.length === 0" class="card empty-state">
            <p>Loading products...</p>
        </div>

        <div v-else-if="products.length" class="grid grid-3">
            <article v-for="product in products" :key="product.id" class="card product-card">
                <h3 class="product-card__title">{{ product.name }}</h3>
                <p class="muted">{{ product.short_description }}</p>
                <p class="product-card__price">
                    From {{ formatPrice(product.variants?.[0]?.price) }}
                    {{ product.variants?.[0]?.currency ?? "USD" }}
                </p>
                <div class="product-card__actions">
                    <RouterLink class="btn btn-muted" :to="`/product/${product.slug}`"
                        >Open product</RouterLink
                    >
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

        <div v-else class="card empty-state">
            <p>No products found for current filters.</p>
        </div>
    </section>
</template>

<script setup lang="ts">
import { RouterLink } from "vue-router";

import { useCatalogProducts } from "@/composables/useCatalogProducts";
import { useCartStore } from "@/stores/cart";

const cartStore = useCartStore();
const { products, filters, isLoading, loadError, applyFilters } = useCatalogProducts();

const formatPrice = (price: number | undefined): string => {
    return Number(price ?? 0).toFixed(2);
};

const addToCart = async (variantId: number): Promise<void> => {
    await cartStore.addOneItem(variantId);
};
</script>
