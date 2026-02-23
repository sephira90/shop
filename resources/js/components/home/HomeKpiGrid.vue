<template>
    <AppGridThreeColumns>
        <HomeKpiCard
            value="Fast UI shell"
            label="Optimized SPA routing with clean refresh support."
        />
        <HomeKpiCard
            value="Catalog first"
            label="Product discovery, variant selection and cart flow."
        />
        <HomeKpiCard
            value="Admin ready"
            label="Dedicated pages for orders, products and promotions."
        />
    </AppGridThreeColumns>
</template>

<script setup lang="ts">
import AppGridThreeColumns from "@/components/ui/AppGridThreeColumns.vue";
import HomeKpiCard from "@/components/home/HomeKpiCard.vue";
</script>
