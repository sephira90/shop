import { computed, reactive, ref } from "vue";

import {
    resolveAuthGuestTokenStorageAdapter,
    type AuthGuestTokenStorageAdapter,
} from "@/composables/auth/authPageEffects";
import { useApiError } from "@/composables/useApiError";
import { useAuthStore } from "@/stores/auth";

type AuthMode = "login" | "register";

interface UseAuthPageViewModelOptions {
    routeRedirectQuery: unknown;
    replaceRoute: (path: string) => Promise<void>;
    guestTokenStorage?: Partial<AuthGuestTokenStorageAdapter>;
}

export const useAuthPageViewModel = ({
    routeRedirectQuery,
    replaceRoute,
    guestTokenStorage: guestTokenStorageOption,
}: UseAuthPageViewModelOptions) => {
    const authStore = useAuthStore();
    const { parseApiError } = useApiError();
    const guestTokenStorage = resolveAuthGuestTokenStorageAdapter(guestTokenStorageOption);

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
        if (typeof routeRedirectQuery === "string" && routeRedirectQuery.startsWith("/")) {
            return routeRedirectQuery;
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
            const guestToken = guestTokenStorage.getGuestToken() ?? undefined;
            await authStore.login({
                email: loginForm.email,
                password: loginForm.password,
                guest_token: guestToken,
            });
            await authStore.ensureUserLoaded();
            await replaceRoute(resolveRedirectPath());
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
            await replaceRoute(resolveRedirectPath());
        } catch (error: unknown) {
            errorMessage.value = parseApiError(error, "Authentication failed.");
        } finally {
            isSubmitting.value = false;
        }
    };

    return {
        isLoginMode,
        errorMessage,
        isSubmitting,
        loginForm,
        registerForm,
        toggleMode,
        submitLogin,
        submitRegister,
    };
};
