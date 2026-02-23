<template>
    <label class="field">
        <span class="field__label">{{ label }}</span>
        <slot />
    </label>
</template>

<script setup lang="ts">
defineProps<{
    label: string;
}>();
</script>
