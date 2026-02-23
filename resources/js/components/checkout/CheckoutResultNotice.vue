<template>
    <AppNotice :message="message" :variant="isSuccess ? 'success' : 'error'" />
</template>

<script setup lang="ts">
import AppNotice from "@/components/ui/AppNotice.vue";

defineProps<{
    message: string;
    isSuccess: boolean;
}>();
</script>
