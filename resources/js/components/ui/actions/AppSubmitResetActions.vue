<template>
    <AppActionsRow :with-top-spacing="withTopSpacing">
        <slot name="primary" />
        <slot name="secondary" />
    </AppActionsRow>
</template>

<script setup lang="ts">
import AppActionsRow from "@/components/ui/actions/AppActionsRow.vue";

withDefaults(
    defineProps<{
        withTopSpacing?: boolean;
    }>(),
    {
        withTopSpacing: false,
    },
);
</script>
