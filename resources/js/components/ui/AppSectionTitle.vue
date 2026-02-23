<template>
    <component :is="tag" class="section-title">
        <slot />
    </component>
</template>

<script setup lang="ts">
withDefaults(
    defineProps<{
        tag?: string;
    }>(),
    {
        tag: "h2",
    },
);
</script>
