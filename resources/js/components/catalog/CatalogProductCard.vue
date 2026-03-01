<template>
    <AppCard tag="article" class="product-card">
        <h3 class="product-card__title">{{ product.name }}</h3>
        <AppMutedText>{{ product.short_description }}</AppMutedText>
        <p class="product-card__price">
            From {{ formatPrice(primaryVariant?.price, primaryVariant?.currency) }}
        </p>
        <div class="product-card__actions">
            <AppButton variant="muted" :to="`/product/${product.slug}`"> Open product </AppButton>
            <AppButton
                v-if="primaryVariant"
                variant="primary"
                type="button"
                @click="$emit('addToCart', primaryVariant.id)"
            >
                Add to cart
            </AppButton>
        </div>
    </AppCard>
</template>

<script setup lang="ts">
import { computed } from "vue";

import AppButton from "@/components/ui/actions/AppButton.vue";
import AppCard from "@/components/ui/layout/AppCard.vue";
import AppMutedText from "@/components/ui/typography/AppMutedText.vue";
import type { CatalogProduct } from "@/types/catalog";

const props = defineProps<{
    product: CatalogProduct;
    formatPrice: (price: number | undefined, currency?: string) => string;
}>();

defineEmits<{
    (event: "addToCart", variantId: number): void;
}>();

const primaryVariant = computed(() => props.product.variants?.[0]);
</script>
