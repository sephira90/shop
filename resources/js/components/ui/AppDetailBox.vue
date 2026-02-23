<template>
    <article class="order-detail-box">
        <h3>{{ title }}</h3>
        <p class="muted">{{ content }}</p>
    </article>
</template>

<script setup lang="ts">
defineProps<{
    title: string;
    content: string;
}>();
</script>
