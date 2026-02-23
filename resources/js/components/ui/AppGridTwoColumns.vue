<template>
    <component :is="tag" class="grid grid-2" :class="{ 'actions--top': withTopSpacing }">
        <slot />
    </component>
</template>

<script setup lang="ts">
withDefaults(
    defineProps<{
        withTopSpacing?: boolean;
        tag?: string;
    }>(),
    {
        withTopSpacing: false,
        tag: "div",
    },
);
</script>
