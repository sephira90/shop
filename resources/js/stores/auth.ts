import { defineStore } from "pinia";

import { getAuthProfile, loginAuth, registerAuth, updateAuthProfile } from "@/api/auth";
import { apiClient } from "@/api/client";
import type {
    AuthLoginPayload,
    AuthRegisterPayload,
    AuthUpdateProfilePayload,
    AuthUser,
    RoleName,
} from "@/types/auth";
import { createBrowserStorageAdapter, type StorageAdapter } from "@/utils/storage";

const AUTH_TOKEN_STORAGE_KEY = "shop_api_token";

let authStoreStorageAdapter: StorageAdapter = createBrowserStorageAdapter();

const readStoredAuthToken = (): string => {
    return authStoreStorageAdapter.getItem(AUTH_TOKEN_STORAGE_KEY) ?? "";
};

const persistAuthToken = (token: string): void => {
    authStoreStorageAdapter.setItem(AUTH_TOKEN_STORAGE_KEY, token);
};

const clearPersistedAuthToken = (): void => {
    authStoreStorageAdapter.removeItem(AUTH_TOKEN_STORAGE_KEY);
};

export const setAuthStoreStorageAdapterForTests = (adapter: StorageAdapter): void => {
    authStoreStorageAdapter = adapter;
};

export const resetAuthStoreStorageAdapterForTests = (): void => {
    authStoreStorageAdapter = createBrowserStorageAdapter();
};

export const useAuthStore = defineStore("auth", {
    state: () => ({
        token: readStoredAuthToken(),
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
        clearSession(): void {
            this.token = "";
            this.user = null;
            clearPersistedAuthToken();
        },
        async login(payload: AuthLoginPayload): Promise<void> {
            const response = await loginAuth(payload);

            this.token = response.token;
            this.user = response.user;
            persistAuthToken(this.token);
        },
        async register(payload: AuthRegisterPayload): Promise<void> {
            const response = await registerAuth(payload);

            this.token = response.token;
            this.user = response.user;
            persistAuthToken(this.token);
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
        async logout(options?: { revokeRemote?: boolean }): Promise<void> {
            if ((options?.revokeRemote ?? true) && this.token) {
                try {
                    await apiClient.post("/auth/logout");
                } catch {
                    // Ignore API errors and always clear local auth state.
                }
            }

            this.clearSession();
        },
    },
});
