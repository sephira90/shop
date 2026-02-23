<template>
    <component :is="inCard ? AppCard : tag" :tag="inCard ? tag : undefined" class="empty-state">
        <p>{{ message }}</p>
    </component>
</template>

<script setup lang="ts">
import AppCard from "@/components/ui/AppCard.vue";

withDefaults(
    defineProps<{
        message: string;
        tag?: "div" | "section" | "article";
        inCard?: boolean;
    }>(),
    {
        tag: "div",
        inCard: false,
    },
);
</script>
