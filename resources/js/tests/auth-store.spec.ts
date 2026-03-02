import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { createPinia, setActivePinia } from "pinia";

vi.mock("@/api/auth", () => ({
    getAuthProfile: vi.fn(),
    loginAuth: vi.fn(),
    registerAuth: vi.fn(),
    updateAuthProfile: vi.fn(),
}));

vi.mock("@/api/client", () => ({
    apiClient: {
        post: vi.fn(),
    },
}));

import { loginAuth } from "@/api/auth";
import {
    resetAuthStoreStorageAdapterForTests,
    setAuthStoreStorageAdapterForTests,
    useAuthStore,
} from "@/stores/auth";
import { createInMemoryStorageAdapter } from "@/utils/storage";

const loginAuthMock = loginAuth as unknown as ReturnType<typeof vi.fn>;

describe("auth store", () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();
    });

    afterEach(() => {
        resetAuthStoreStorageAdapterForTests();
    });

    it("hydrates token from injected storage", () => {
        const storage = createInMemoryStorageAdapter({
            shop_api_token: "persisted-token",
        });

        setAuthStoreStorageAdapterForTests(storage);
        const authStore = useAuthStore();

        expect(authStore.token).toBe("persisted-token");
    });

    it("persists login token via injected storage", async () => {
        const storage = createInMemoryStorageAdapter();
        setAuthStoreStorageAdapterForTests(storage);
        loginAuthMock.mockResolvedValue({
            token: "token-1",
            user: {
                id: 1,
                first_name: "Jane",
                last_name: "Doe",
                name: "Jane Doe",
                email: "jane@example.com",
                roles: ["customer"],
                phone: null,
                is_email_verified: true,
            },
        });

        const authStore = useAuthStore();
        await authStore.login({
            email: "jane@example.com",
            password: "secret",
        });

        expect(storage.getItem("shop_api_token")).toBe("token-1");
    });

    it("clears persisted token on clearSession", () => {
        const storage = createInMemoryStorageAdapter({
            shop_api_token: "token-9",
        });

        setAuthStoreStorageAdapterForTests(storage);
        const authStore = useAuthStore();
        authStore.user = {
            id: 9,
            first_name: "Admin",
            last_name: "User",
            name: "Admin User",
            email: "admin@example.com",
            roles: ["admin"],
            phone: null,
            is_email_verified: true,
        };

        authStore.clearSession();

        expect(authStore.token).toBe("");
        expect(authStore.user).toBeNull();
        expect(storage.getItem("shop_api_token")).toBeNull();
    });
});
