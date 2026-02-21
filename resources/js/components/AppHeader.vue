<template>
    <header class="app-header">
        <div class="container app-header__inner">
            <RouterLink to="/" class="brand">Shop</RouterLink>
            <div class="app-header__actions">
                <nav class="app-nav" aria-label="Main navigation">
                    <RouterLink to="/catalog" class="app-nav__link">Catalog</RouterLink>
                    <RouterLink to="/cart" class="app-nav__link">Cart</RouterLink>
                    <RouterLink v-if="canAccessAccount" to="/account/profile" class="app-nav__link"
                        >Account</RouterLink
                    >
                    <RouterLink v-if="canAccessAdmin" to="/admin" class="app-nav__link"
                        >Admin</RouterLink
                    >
                    <RouterLink v-if="!authStore.isAuthenticated" to="/auth" class="app-nav__link"
                        >Sign in</RouterLink
                    >
                </nav>
                <button
                    v-if="canUseThemeToggle"
                    class="theme-toggle"
                    type="button"
                    :aria-label="toggleLabel"
                    @click="toggleTheme"
                >
                    <span class="theme-toggle__icon" aria-hidden="true">{{
                        theme === "dark" ? "D" : "L"
                    }}</span>
                    <span>{{ theme === "dark" ? "Dark" : "Light" }}</span>
                </button>
                <button
                    v-if="authStore.isAuthenticated"
                    class="btn btn-muted"
                    type="button"
                    @click="logout"
                >
                    Logout
                </button>
            </div>
        </div>
    </header>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import { RouterLink, useRouter } from "vue-router";
import { useAuthStore } from "@/stores/auth";

type Theme = "light" | "dark";

const authStore = useAuthStore();
const router = useRouter();
const theme = ref<Theme>("light");
const toggleLabel = "Toggle color theme";
const canAccessAdmin = computed<boolean>(() => authStore.canAccessAdmin);
const canAccessAccount = computed<boolean>(() => authStore.canAccessAccount);
const canUseThemeToggle = computed<boolean>(() => canAccessAdmin.value || canAccessAccount.value);

const applyTheme = (nextTheme: Theme): void => {
    document.documentElement.dataset.theme = nextTheme;
    localStorage.setItem("shop_theme", nextTheme);
    theme.value = nextTheme;
};

const toggleTheme = (): void => {
    if (!canUseThemeToggle.value) {
        return;
    }

    applyTheme(theme.value === "dark" ? "light" : "dark");
};

const logout = async (): Promise<void> => {
    await authStore.logout();
    await router.push("/");
};

onMounted(() => {
    const persistedTheme = localStorage.getItem("shop_theme");

    if (persistedTheme === "light" || persistedTheme === "dark") {
        applyTheme(persistedTheme);
        return;
    }

    const preferredTheme = window.matchMedia("(prefers-color-scheme: dark)").matches
        ? "dark"
        : "light";
    applyTheme(preferredTheme);
});
</script>
