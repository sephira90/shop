import { defineStore } from "pinia";

import { getAuthProfile, loginAuth, registerAuth, updateAuthProfile } from "@/api/auth";
import { apiClient } from "@/api/client";
import type {
    AuthLoginPayload,
    AuthRegisterPayload,
    AuthUpdateProfilePayload,
    AuthUser,
} from "@/types/auth";

type RoleName = "customer" | "manager" | "admin";

export const useAuthStore = defineStore("auth", {
    state: () => ({
        token: localStorage.getItem("shop_api_token") ?? "",
        user: null as AuthUser | null,
    }),
    getters: {
        isAuthenticated: (state) => state.token.length > 0,
        isAdmin: (state) => state.user?.roles.includes("admin") ?? false,
        isManager: (state) => state.user?.roles.includes("manager") ?? false,
        canAccessAdmin(): boolean {
            return this.isAdmin || this.isManager;
        },
        canAccessAccount(state): boolean {
            if (!state.user) {
                return false;
            }

            return state.user.roles.some((role): boolean =>
                ["customer", "manager", "admin"].includes(role),
            );
        },
    },
    actions: {
        hasRole(role: RoleName): boolean {
            return this.user?.roles.includes(role) ?? false;
        },
        async login(payload: AuthLoginPayload): Promise<void> {
            const response = await loginAuth(payload);

            this.token = response.token;
            this.user = response.user;
            localStorage.setItem("shop_api_token", this.token);
        },
        async register(payload: AuthRegisterPayload): Promise<void> {
            const response = await registerAuth(payload);

            this.token = response.token;
            this.user = response.user;
            localStorage.setItem("shop_api_token", this.token);
        },
        async fetchMe(): Promise<void> {
            if (!this.token) {
                return;
            }

            this.user = await getAuthProfile();
        },
        async ensureUserLoaded(): Promise<void> {
            if (!this.token || this.user) {
                return;
            }

            await this.fetchMe();
        },
        async updateProfile(payload: AuthUpdateProfilePayload): Promise<void> {
            this.user = await updateAuthProfile(payload);
        },
        async logout(): Promise<void> {
            if (this.token) {
                try {
                    await apiClient.post("/auth/logout");
                } catch {
                    // Ignore API errors and always clear local auth state.
                }
            }

            this.token = "";
            this.user = null;
            localStorage.removeItem("shop_api_token");
        },
    },
});
