<template>
    <AppCard tag="aside">
        <AppSectionTitle>Purchase</AppSectionTitle>
        <p class="product-card__price">
            {{ formatPrice(selectedVariant?.price) }} {{ selectedVariant?.currency ?? "USD" }}
        </p>
        <div class="grid">
            <AppSelectInput v-model.number="selectedVariantId">
                <option v-for="variant in product.variants" :key="variant.id" :value="variant.id">
                    {{ variant.name }} - {{ formatPrice(variant.price) }} {{ variant.currency }}
                </option>
            </AppSelectInput>
            <AppButton
                variant="primary"
                type="button"
                :disabled="!selectedVariantId"
                @click="$emit('addToCart')"
            >
                Add to cart
            </AppButton>
        </div>
    </AppCard>
</template>

<script setup lang="ts">
import AppButton from "@/components/ui/AppButton.vue";
import AppCard from "@/components/ui/AppCard.vue";
import AppSelectInput from "@/components/ui/AppSelectInput.vue";
import AppSectionTitle from "@/components/ui/AppSectionTitle.vue";
import type { CatalogProduct, CatalogProductVariant } from "@/types/catalog";

defineProps<{
    product: CatalogProduct;
    selectedVariant: CatalogProductVariant | null;
    formatPrice: (price: number | undefined) => string;
}>();

defineEmits<{
    (event: "addToCart"): void;
}>();

const selectedVariantId = defineModel<number | null>("selectedVariantId", { required: true });
</script>
