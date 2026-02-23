<template>
    <div class="stack stack--between" :class="{ 'actions--top': withTopSpacing }">
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
