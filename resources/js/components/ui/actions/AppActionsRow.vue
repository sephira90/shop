<template>
    <div class="actions" :class="{ 'actions--top': withTopSpacing }">
        <slot />
    </div>
</template>

<script setup lang="ts">
withDefaults(
    defineProps<{
        withTopSpacing?: boolean;
    }>(),
    {
        withTopSpacing: false,
    },
);
</script>
