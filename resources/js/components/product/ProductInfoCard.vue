<template>
    <AppCard tag="article">
        <AppSectionTitle tag="h1">{{ product.name }}</AppSectionTitle>
        <AppMutedText>{{ product.description }}</AppMutedText>
    </AppCard>
</template>

<script setup lang="ts">
import AppCard from "@/components/ui/layout/AppCard.vue";
import AppMutedText from "@/components/ui/typography/AppMutedText.vue";
import AppSectionTitle from "@/components/ui/typography/AppSectionTitle.vue";
import type { CatalogProduct } from "@/types/catalog";

defineProps<{
    product: CatalogProduct;
}>();
</script>
