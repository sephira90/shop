<template>
    <div class="site-shell">
        <AppHeader />
        <main class="site-main">
            <div class="container">
                <RouterView />
            </div>
        </main>
    </div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue';
import { RouterView } from 'vue-router';
import AppHeader from '@/components/AppHeader.vue';
import { useAuthStore } from '@/stores/auth';

const authStore = useAuthStore();

onMounted(async () => {
    if (!authStore.token) {
        return;
    }

    try {
        await authStore.ensureUserLoaded();
    } catch {
        await authStore.logout();
    }
});
</script>
