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
import { computed, reactive, ref } from "vue";
import { useRoute, useRouter } from "vue-router";

import AppCard from "@/components/ui/layout/AppCard.vue";
import AppNotice from "@/components/ui/feedback/AppNotice.vue";
import AppGridTwoColumns from "@/components/ui/layout/AppGridTwoColumns.vue";
import AuthHeroCard from "@/components/auth/AuthHeroCard.vue";
import AuthLoginForm from "@/components/auth/AuthLoginForm.vue";
import AuthModeSwitcher from "@/components/auth/AuthModeSwitcher.vue";
import AuthRegisterForm from "@/components/auth/AuthRegisterForm.vue";
import { useApiError } from "@/composables/useApiError";
import { useAuthStore } from "@/stores/auth";

type AuthMode = "login" | "register";

const authStore = useAuthStore();
const { parseApiError } = useApiError();
const route = useRoute();
const router = useRouter();
const mode = ref<AuthMode>("login");
const errorMessage = ref("");
const isSubmitting = ref(false);
const isLoginMode = computed<boolean>(() => mode.value === "login");
const loginForm = reactive({
    email: "",
    password: "",
});
const registerForm = reactive({
    first_name: "",
    last_name: "",
    email: "",
    password: "",
    password_confirmation: "",
});

const resolveRedirectPath = (): string => {
    const redirect = route.query.redirect;

    if (typeof redirect === "string" && redirect.startsWith("/")) {
        return redirect;
    }

    if (authStore.canAccessAdmin) {
        return "/admin";
    }

    if (authStore.canAccessAccount) {
        return "/account/profile";
    }

    return "/";
};

const toggleMode = (): void => {
    mode.value = isLoginMode.value ? "register" : "login";
    errorMessage.value = "";
};

const submitLogin = async (): Promise<void> => {
    isSubmitting.value = true;
    errorMessage.value = "";

    try {
        const guestToken = localStorage.getItem("shop_guest_token") ?? undefined;
        await authStore.login({
            email: loginForm.email,
            password: loginForm.password,
            guest_token: guestToken,
        });
        await authStore.ensureUserLoaded();
        await router.replace(resolveRedirectPath());
    } catch (error: unknown) {
        errorMessage.value = parseApiError(error, "Authentication failed.");
    } finally {
        isSubmitting.value = false;
    }
};

const submitRegister = async (): Promise<void> => {
    isSubmitting.value = true;
    errorMessage.value = "";

    try {
        await authStore.register(registerForm);
        await authStore.ensureUserLoaded();
        await router.replace(resolveRedirectPath());
    } catch (error: unknown) {
        errorMessage.value = parseApiError(error, "Authentication failed.");
    } finally {
        isSubmitting.value = false;
    }
};
</script>
