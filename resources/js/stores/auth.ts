import { defineStore } from 'pinia';

import { apiClient } from '@/api/client';

export interface AuthUser {
    id: number;
    name: string;
    email: string;
    roles: string[];
}

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

export const useAuthStore = defineStore('auth', {
    state: () => ({
        token: localStorage.getItem('shop_api_token') ?? '',
        user: null as AuthUser | null,
    }),
    getters: {
        isAuthenticated: (state) => state.token.length > 0,
        isAdmin: (state) => state.user?.roles.includes('admin') ?? false,
    },
    actions: {
        async login(payload: LoginPayload): Promise<void> {
            const { data } = await apiClient.post('/auth/login', payload);
            this.token = data.data.token;
            this.user = data.data.user;
            localStorage.setItem('shop_api_token', this.token);
        },
        async register(payload: RegisterPayload): Promise<void> {
            const { data } = await apiClient.post('/auth/register', payload);
            this.token = data.data.token;
            this.user = data.data.user;
            localStorage.setItem('shop_api_token', this.token);
        },
        async fetchMe(): Promise<void> {
            if (!this.token) {
                return;
            }

            const { data } = await apiClient.get('/auth/me');
            this.user = data.data;
        },
        async logout(): Promise<void> {
            if (this.token) {
                await apiClient.post('/auth/logout');
            }

            this.token = '';
            this.user = null;
            localStorage.removeItem('shop_api_token');
        },
    },
});
