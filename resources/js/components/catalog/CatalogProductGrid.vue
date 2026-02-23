<template>
    <AppGridThreeColumns>
        <CatalogProductCard
            v-for="product in products"
            :key="product.id"
            :product="product"
            :format-price="formatPrice"
            @add-to-cart="$emit('addToCart', $event)"
        />
    </AppGridThreeColumns>
</template>

<script setup lang="ts">
import AppGridThreeColumns from "@/components/ui/AppGridThreeColumns.vue";
import type { CatalogProduct } from "@/types/catalog";

import CatalogProductCard from "./CatalogProductCard.vue";

defineProps<{
    products: CatalogProduct[];
    formatPrice: (price: number | undefined) => string;
}>();

defineEmits<{
    (event: "addToCart", variantId: number): void;
}>();
</script>
