<template>
    <AppGridThreeColumns>
        <AdminDashboardNavCard
            v-for="item in items"
            :key="item.to"
            :to="item.to"
            :title="item.title"
            :description="item.description"
            :icon-path="item.iconPath"
        />
    </AppGridThreeColumns>
</template>

<script setup lang="ts">
import AppGridThreeColumns from "@/components/ui/layout/AppGridThreeColumns.vue";
import AdminDashboardNavCard from "./AdminDashboardNavCard.vue";

defineProps<{
    items: Array<{
        to: string;
        title: string;
        description: string;
        iconPath: string;
    }>;
}>();
</script>
