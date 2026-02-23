<template>
    <component :is="tag" class="card">
        <slot />
    </component>
</template>

<script setup lang="ts">
import type { Component } from "vue";

withDefaults(
    defineProps<{
        tag?: string | Component;
    }>(),
    {
        tag: "div",
    },
);
</script>
