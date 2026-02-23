<template>
    <AppCard :tag="RouterLink" class="card-link" :to="to">
        <span class="feature-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" focusable="false">
                <path :d="iconPath" />
            </svg>
        </span>
        <h2>{{ title }}</h2>
        <AppMutedText>{{ description }}</AppMutedText>
    </AppCard>
</template>

<script setup lang="ts">
import { RouterLink } from "vue-router";
import AppCard from "@/components/ui/layout/AppCard.vue";
import AppMutedText from "@/components/ui/typography/AppMutedText.vue";

defineProps<{
    to: string;
    title: string;
    description: string;
    iconPath: string;
}>();
</script>
