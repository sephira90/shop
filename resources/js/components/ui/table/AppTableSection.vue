<template>
    <div class="table-wrap" :class="{ 'actions--top': withTopSpacing }">
        <table class="table">
            <slot />
        </table>
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
