<template>
    <section v-if="isLoading" class="card empty-state">
        <p>Loading product...</p>
    </section>

    <section v-else-if="product" class="grid grid-2">
        <article class="card">
            <h1 class="section-title">{{ product.name }}</h1>
            <p class="muted">{{ product.description }}</p>
        </article>

        <aside class="card">
            <h2 class="section-title">Purchase</h2>
            <p class="product-card__price">
                {{ formatPrice(selectedVariant?.price) }} {{ selectedVariant?.currency ?? "USD" }}
            </p>
            <div class="grid">
                <select v-model.number="selectedVariantId">
                    <option
                        v-for="variant in product.variants"
                        :key="variant.id"
                        :value="variant.id"
                    >
                        {{ variant.name }} - {{ formatPrice(variant.price) }} {{ variant.currency }}
                    </option>
                </select>
                <button
                    class="btn btn-primary"
                    type="button"
                    :disabled="!selectedVariantId"
                    @click="addToCart"
                >
                    Add to cart
                </button>
            </div>
        </aside>
    </section>

    <section v-else class="card empty-state">
        <p>{{ loadError || "Product is unavailable right now." }}</p>
    </section>
</template>

<script setup lang="ts">
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
