<template>
    <section class="grid">
        <CatalogFiltersCard
            v-model:query="filters.q"
            v-model:sort="filters.sort"
            :is-loading="isLoading"
            :load-error="loadError"
            @apply="applyFilters"
        />

        <CatalogEmptyState
            v-if="isLoading && products.length === 0"
            message="Loading products..."
        />

        <CatalogProductGrid
            v-else-if="products.length"
            :products="products"
            :format-price="formatPrice"
            @add-to-cart="addToCart"
        />

        <CatalogEmptyState v-else message="No products found for current filters." />
    </section>
</template>

<script setup lang="ts">
import CatalogEmptyState from "@/components/catalog/CatalogEmptyState.vue";
import CatalogFiltersCard from "@/components/catalog/CatalogFiltersCard.vue";
import CatalogProductGrid from "@/components/catalog/CatalogProductGrid.vue";
import { useCatalogProducts } from "@/composables/useCatalogProducts";
import { useCartStore } from "@/stores/cart";
import { formatPrice } from "@/utils/format";

const cartStore = useCartStore();
const { products, filters, isLoading, loadError, applyFilters } = useCatalogProducts();

const addToCart = async (variantId: number): Promise<void> => {
    await cartStore.addOneItem(variantId);
};
</script>
