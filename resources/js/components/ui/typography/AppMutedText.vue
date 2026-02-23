<template>
    <component :is="tag" class="muted">
        <slot />
    </component>
</template>

<script setup lang="ts">
withDefaults(
    defineProps<{
        tag?: string;
    }>(),
    {
        tag: "p",
    },
);
</script>
