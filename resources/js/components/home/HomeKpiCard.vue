<template>
    <AppCard tag="article" class="kpi">
        <span class="kpi__value">{{ value }}</span>
        <span class="kpi__label">{{ label }}</span>
    </AppCard>
</template>

<script setup lang="ts">
import AppCard from "@/components/ui/AppCard.vue";

defineProps<{
    value: string;
    label: string;
}>();
</script>
