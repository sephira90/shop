<template>
    <div class="site-shell">
        <AppHeader />
        <main class="site-main">
            <div class="container">
                <AppNotice
                    v-if="appShellStore.notice.message"
                    :message="appShellStore.notice.message"
                    :variant="appShellStore.notice.type"
                />
                <RouterView />
            </div>
        </main>
    </div>
</template>

<script setup lang="ts">
import { onMounted } from "vue";
import { RouterView } from "vue-router";
import AppHeader from "@/components/AppHeader.vue";
import AppNotice from "@/components/ui/feedback/AppNotice.vue";
import { useAppShellStore } from "@/stores/app-shell";
import { useAuthStore } from "@/stores/auth";

const appShellStore = useAppShellStore();
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
