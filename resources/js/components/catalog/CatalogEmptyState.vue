<template>
    <AppEmptyState in-card :message="message" />
</template>

<script setup lang="ts">
import AppEmptyState from "@/components/ui/AppEmptyState.vue";

defineProps<{
    message: string;
}>();
</script>
