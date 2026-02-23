<template>
    <div class="variant-section actions--top">
        <div class="variant-section__header">
            <h2 class="variant-section__title">Variants and pricing</h2>
            <AppButton variant="muted" type="button" @click="$emit('addVariant')">
                Add variant
            </AppButton>
        </div>
        <p class="muted variant-section__hint">
            Each variant controls its own price and inventory values.
        </p>

        <div class="variant-list">
            <AdminProductVariantCard
                v-for="(variant, index) in variants"
                :key="variant.local_id"
                v-model:variant="variants[index]"
                :index="index"
                :can-remove="variants.length > 1"
                @remove="$emit('removeVariant', index)"
            />
        </div>
    </div>
</template>

<script setup lang="ts">
import type { ProductVariantForm } from "@/types/admin-products";

import AppButton from "@/components/ui/AppButton.vue";
import AdminProductVariantCard from "@/components/admin/products/AdminProductVariantCard.vue";

defineEmits<{
    (event: "addVariant"): void;
    (event: "removeVariant", index: number): void;
}>();

const variants = defineModel<ProductVariantForm[]>("variants", { required: true });
</script>
