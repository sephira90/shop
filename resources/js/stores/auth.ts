import { defineStore } from 'pinia';

import { apiClient } from '@/api/client';
import { extractData } from '@/api/response';

export interface AuthUser {
    id: number;
    first_name?: string | null;
    last_name?: string | null;
    name: string;
    email: string;
    roles: string[];
    phone?: string | null;
    is_email_verified?: boolean;
}

type RoleName = 'customer' | 'manager' | 'admin';

interface LoginPayload {
    email: string;
    password: string;
    guest_token?: string;
}

interface RegisterPayload {
    first_name: string;
    last_name: string;
    email: string;
    password: string;
    password_confirmation: string;
}

interface UpdateProfilePayload {
    first_name: string;
    last_name: string;
    phone: string | null;
}

export const useAuthStore = defineStore('auth', {
    state: () => ({
        token: localStorage.getItem('shop_api_token') ?? '',
        user: null as AuthUser | null,
    }),
    getters: {
        isAuthenticated: (state) => state.token.length > 0,
        isAdmin: (state) => state.user?.roles.includes('admin') ?? false,
        isManager: (state) => state.user?.roles.includes('manager') ?? false,
        canAccessAdmin(): boolean {
            return this.isAdmin || this.isManager;
        },
        canAccessAccount(state): boolean {
            if (!state.user) {
                return false;
            }

            return state.user.roles.some((role): boolean => ['customer', 'manager', 'admin'].includes(role));
        },
    },
    actions: {
        hasRole(role: RoleName): boolean {
            return this.user?.roles.includes(role) ?? false;
        },
        async login(payload: LoginPayload): Promise<void> {
            const { data } = await apiClient.post('/auth/login', payload);
            const response = extractData<{ token: string; user: AuthUser }>(data);

            if (!response) {
                throw new Error('Invalid login response payload.');
            }

            this.token = response.token;
            this.user = response.user;
            localStorage.setItem('shop_api_token', this.token);
        },
        async register(payload: RegisterPayload): Promise<void> {
            const { data } = await apiClient.post('/auth/register', payload);
            const response = extractData<{ token: string; user: AuthUser }>(data);

            if (!response) {
                throw new Error('Invalid register response payload.');
            }

            this.token = response.token;
            this.user = response.user;
            localStorage.setItem('shop_api_token', this.token);
        },
        async fetchMe(): Promise<void> {
            if (!this.token) {
                return;
            }

            const { data } = await apiClient.get('/auth/me');
            const response = extractData<AuthUser>(data);

            if (!response) {
                throw new Error('Invalid profile response payload.');
            }

            this.user = response;
        },
        async ensureUserLoaded(): Promise<void> {
            if (!this.token || this.user) {
                return;
            }

            await this.fetchMe();
        },
        async updateProfile(payload: UpdateProfilePayload): Promise<void> {
            const { data } = await apiClient.patch('/auth/profile', payload);
            const response = extractData<AuthUser>(data);

            if (!response) {
                throw new Error('Invalid profile update response payload.');
            }

            this.user = response;
        },
        async logout(): Promise<void> {
            if (this.token) {
                try {
                    await apiClient.post('/auth/logout');
                } catch {
                    // Ignore API errors and always clear local auth state.
                }
            }

            this.token = '';
            this.user = null;
            localStorage.removeItem('shop_api_token');
        },
    },
});
