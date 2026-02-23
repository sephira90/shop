<template>
    <AppEmptyState v-if="isLoading" tag="section" in-card message="Loading product..." />

    <AppGridTwoColumns v-else-if="product" tag="section">
        <ProductInfoCard :product="product" />

        <ProductPurchaseCard
            v-model:selected-variant-id="selectedVariantId"
            :product="product"
            :selected-variant="selectedVariant"
            :format-price="formatPrice"
            @add-to-cart="addToCart"
        />
    </AppGridTwoColumns>

    <AppEmptyState
        v-else
        tag="section"
        in-card
        :message="loadError || 'Product is unavailable right now.'"
    />
</template>

<script setup lang="ts">
import ProductInfoCard from "@/components/product/ProductInfoCard.vue";
import ProductPurchaseCard from "@/components/product/ProductPurchaseCard.vue";
import AppEmptyState from "@/components/ui/AppEmptyState.vue";
import AppGridTwoColumns from "@/components/ui/AppGridTwoColumns.vue";
import { useCatalogProduct } from "@/composables/useCatalogProduct";
import { useCartStore } from "@/stores/cart";
const cartStore = useCartStore();
const { product, selectedVariantId, selectedVariant, isLoading, loadError } = useCatalogProduct();

const formatPrice = (price: number | undefined): string => {
    return Number(price ?? 0).toFixed(2);
};

const addToCart = async (): Promise<void> => {
    if (!selectedVariantId.value) {
        return;
    }

    await cartStore.addOneItem(selectedVariantId.value);
};
</script>
