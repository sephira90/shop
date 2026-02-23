<template>
    <AppGridTwoColumns tag="section">
        <AuthHeroCard />

        <AppCard tag="article">
            <AuthModeSwitcher :is-login-mode="isLoginMode" @toggle="toggleMode" />

            <AuthLoginForm
                v-if="isLoginMode"
                v-model:email="loginForm.email"
                v-model:password="loginForm.password"
                :is-submitting="isSubmitting"
                @submit="submitLogin"
            />

            <AuthRegisterForm
                v-else
                v-model:first-name="registerForm.first_name"
                v-model:last-name="registerForm.last_name"
                v-model:email="registerForm.email"
                v-model:password="registerForm.password"
                v-model:password-confirmation="registerForm.password_confirmation"
                :is-submitting="isSubmitting"
                @submit="submitRegister"
            />

            <AppNotice v-if="errorMessage" :message="errorMessage" />
        </AppCard>
    </AppGridTwoColumns>
</template>

<script setup lang="ts">
import { useRoute, useRouter } from "vue-router";

import AppCard from "@/components/ui/layout/AppCard.vue";
import AppNotice from "@/components/ui/feedback/AppNotice.vue";
import AppGridTwoColumns from "@/components/ui/layout/AppGridTwoColumns.vue";
import AuthHeroCard from "@/components/auth/AuthHeroCard.vue";
import AuthLoginForm from "@/components/auth/AuthLoginForm.vue";
import AuthModeSwitcher from "@/components/auth/AuthModeSwitcher.vue";
import AuthRegisterForm from "@/components/auth/AuthRegisterForm.vue";
import { createBrowserAuthGuestTokenStorage } from "@/composables/auth/authPageEffects";
import { useAuthPageViewModel } from "@/composables/auth/useAuthPageViewModel";

const route = useRoute();
const router = useRouter();

const {
    isLoginMode,
    errorMessage,
    isSubmitting,
    loginForm,
    registerForm,
    toggleMode,
    submitLogin,
    submitRegister,
} = useAuthPageViewModel({
    routeRedirectQuery: route.query.redirect,
    replaceRoute: async (path) => {
        await router.replace(path);
    },
    guestTokenStorage: createBrowserAuthGuestTokenStorage(),
});
</script>
