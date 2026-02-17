<template>
    <div>
        <AppHeader />
        <main class="container" style="padding-block: 1.5rem">
            <RouterView />
        </main>
    </div>
</template>

<script setup lang="ts">
import { RouterView } from 'vue-router';
import AppHeader from '@/components/AppHeader.vue';
</script>
