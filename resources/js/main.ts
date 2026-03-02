import { createApp } from "vue";
import { createPinia } from "pinia";

import App from "./App.vue";
import { installApiClientResponseHandling } from "./api/client";
import { router } from "./router";
import { useAppShellStore } from "./stores/app-shell";
import { useAuthStore } from "./stores/auth";
import "./bootstrap";

const app = createApp(App);
const pinia = createPinia();

app.use(pinia);
app.use(router);

installApiClientResponseHandling({
    currentPath: () => router.currentRoute.value.fullPath,
    clearAuthSession: async () => {
        const authStore = useAuthStore(pinia);
        await authStore.logout({ revokeRemote: false });
    },
    redirectToAuth: async (redirectPath: string) => {
        await router.push({
            path: "/auth",
            query: redirectPath === "/auth" ? {} : { redirect: redirectPath },
        });
    },
    showForbiddenNotice: (message: string) => {
        useAppShellStore(pinia).showError(message);
    },
});

app.mount("#app");
