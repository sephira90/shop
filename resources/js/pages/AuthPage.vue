<template>
    <section class="grid grid-2">
        <article class="hero">
            <span class="pill">Secure access</span>
            <h1>Sign in to unlock role-based sections</h1>
            <p>
                Account and admin features are available only after authorization with the required
                role.
            </p>
        </article>

        <article class="card">
            <div class="stack stack--between">
                <h1 class="section-title">{{ isLoginMode ? "Login" : "Register" }}</h1>
                <button class="btn btn-muted" type="button" @click="toggleMode">
                    {{ isLoginMode ? "Need account?" : "Have account?" }}
                </button>
            </div>

            <form v-if="isLoginMode" class="grid actions--top" @submit.prevent="submitLogin">
                <input v-model="loginForm.email" type="email" placeholder="Email" required />
                <input
                    v-model="loginForm.password"
                    type="password"
                    placeholder="Password"
                    required
                />
                <button class="btn btn-primary" type="submit" :disabled="isSubmitting">
                    {{ isSubmitting ? "Signing in..." : "Sign in" }}
                </button>
            </form>

            <form v-else class="grid actions--top" @submit.prevent="submitRegister">
                <div class="grid grid-2">
                    <input v-model="registerForm.first_name" placeholder="First name" required />
                    <input v-model="registerForm.last_name" placeholder="Last name" required />
                </div>
                <input v-model="registerForm.email" type="email" placeholder="Email" required />
                <input
                    v-model="registerForm.password"
                    type="password"
                    placeholder="Password"
                    required
                />
                <input
                    v-model="registerForm.password_confirmation"
                    type="password"
                    placeholder="Confirm password"
                    required
                />
                <button class="btn btn-primary" type="submit" :disabled="isSubmitting">
                    {{ isSubmitting ? "Creating..." : "Create account" }}
                </button>
            </form>

            <p v-if="errorMessage" class="notice notice--error">{{ errorMessage }}</p>
        </article>
    </section>
</template>

<script setup lang="ts">
import { computed, reactive, ref } from "vue";
import { useRoute, useRouter } from "vue-router";

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
