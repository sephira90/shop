import { beforeEach, describe, expect, it, vi } from "vitest";
import { effectScope } from "vue";
import { createPinia, setActivePinia } from "pinia";

import { useAuthPageViewModel } from "@/composables/auth/useAuthPageViewModel";
import { useAuthStore } from "@/stores/auth";

beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
});

describe("useAuthPageViewModel", () => {
    it("submits login with injected guest token and route redirect", async () => {
        const authStore = useAuthStore();
        authStore.login = vi.fn(async () => {
            authStore.token = "token-1";
            authStore.user = {
                id: 1,
                name: "Jane Doe",
                email: "jane@example.com",
                roles: ["customer"],
            };
        }) as typeof authStore.login;
        authStore.ensureUserLoaded = vi.fn(async () => {}) as typeof authStore.ensureUserLoaded;
        const replaceRoute = vi.fn(async () => {});

        const scope = effectScope();
        const vm = scope.run(() =>
            useAuthPageViewModel({
                routeRedirectQuery: "/checkout",
                replaceRoute,
                guestTokenStorage: {
                    getGuestToken: () => "guest-token-1",
                },
            }),
        );

        expect(vm).not.toBeNull();
        if (!vm) {
            scope.stop();
            return;
        }

        vm.loginForm.email = "jane@example.com";
        vm.loginForm.password = "secret";
        await vm.submitLogin();

        expect(authStore.login).toHaveBeenCalledWith({
            email: "jane@example.com",
            password: "secret",
            guest_token: "guest-token-1",
        });
        expect(authStore.ensureUserLoaded).toHaveBeenCalledTimes(1);
        expect(replaceRoute).toHaveBeenCalledWith("/checkout");
        expect(vm.errorMessage.value).toBe("");

        scope.stop();
    });

    it("submits register and uses role-based fallback redirect", async () => {
        const authStore = useAuthStore();
        authStore.register = vi.fn(async () => {
            authStore.token = "token-2";
            authStore.user = {
                id: 2,
                name: "Admin User",
                email: "admin@example.com",
                roles: ["admin"],
            };
        }) as typeof authStore.register;
        authStore.ensureUserLoaded = vi.fn(async () => {}) as typeof authStore.ensureUserLoaded;
        const replaceRoute = vi.fn(async () => {});

        const scope = effectScope();
        const vm = scope.run(() =>
            useAuthPageViewModel({
                routeRedirectQuery: "https://malicious.example",
                replaceRoute,
                guestTokenStorage: {
                    getGuestToken: () => null,
                },
            }),
        );

        expect(vm).not.toBeNull();
        if (!vm) {
            scope.stop();
            return;
        }

        vm.registerForm.first_name = "Admin";
        vm.registerForm.last_name = "User";
        vm.registerForm.email = "admin@example.com";
        vm.registerForm.password = "secret";
        vm.registerForm.password_confirmation = "secret";
        await vm.submitRegister();

        expect(authStore.register).toHaveBeenCalledWith({
            first_name: "Admin",
            last_name: "User",
            email: "admin@example.com",
            password: "secret",
            password_confirmation: "secret",
        });
        expect(replaceRoute).toHaveBeenCalledWith("/admin");

        scope.stop();
    });

    it("toggles auth mode and clears stale error", () => {
        const scope = effectScope();
        const vm = scope.run(() =>
            useAuthPageViewModel({
                routeRedirectQuery: null,
                replaceRoute: async () => {},
                guestTokenStorage: {
                    getGuestToken: () => null,
                },
            }),
        );

        expect(vm).not.toBeNull();
        if (!vm) {
            scope.stop();
            return;
        }

        vm.errorMessage.value = "Authentication failed.";
        expect(vm.isLoginMode.value).toBe(true);

        vm.toggleMode();

        expect(vm.isLoginMode.value).toBe(false);
        expect(vm.errorMessage.value).toBe("");

        scope.stop();
    });

    it("shows parsed error when login fails", async () => {
        const authStore = useAuthStore();
        authStore.login = vi.fn(async () => {
            throw new Error("Invalid credentials.");
        }) as typeof authStore.login;
        const replaceRoute = vi.fn(async () => {});

        const scope = effectScope();
        const vm = scope.run(() =>
            useAuthPageViewModel({
                routeRedirectQuery: null,
                replaceRoute,
                guestTokenStorage: {
                    getGuestToken: () => null,
                },
            }),
        );

        expect(vm).not.toBeNull();
        if (!vm) {
            scope.stop();
            return;
        }

        vm.loginForm.email = "jane@example.com";
        vm.loginForm.password = "wrong";
        await vm.submitLogin();

        expect(vm.errorMessage.value).toBe("Invalid credentials.");
        expect(replaceRoute).not.toHaveBeenCalled();

        scope.stop();
    });

    it("shows parsed error when register fails", async () => {
        const authStore = useAuthStore();
        authStore.register = vi.fn(async () => {
            throw new Error("Registration is unavailable.");
        }) as typeof authStore.register;
        const replaceRoute = vi.fn(async () => {});

        const scope = effectScope();
        const vm = scope.run(() =>
            useAuthPageViewModel({
                routeRedirectQuery: null,
                replaceRoute,
                guestTokenStorage: {
                    getGuestToken: () => null,
                },
            }),
        );

        expect(vm).not.toBeNull();
        if (!vm) {
            scope.stop();
            return;
        }

        vm.toggleMode();
        vm.registerForm.first_name = "Jane";
        vm.registerForm.last_name = "Doe";
        vm.registerForm.email = "jane@example.com";
        vm.registerForm.password = "secret";
        vm.registerForm.password_confirmation = "secret";
        await vm.submitRegister();

        expect(vm.errorMessage.value).toBe("Registration is unavailable.");
        expect(vm.isSubmitting.value).toBe(false);
        expect(replaceRoute).not.toHaveBeenCalled();

        scope.stop();
    });
});
