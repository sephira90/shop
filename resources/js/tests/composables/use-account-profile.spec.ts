import { beforeEach, describe, expect, it, vi } from "vitest";
import { effectScope } from "vue";
import { createPinia, setActivePinia } from "pinia";

import { getAccountOrdersSummary } from "@/api/account/orders";
import { useAccountProfile } from "@/composables/useAccountProfile";
import { useAuthStore } from "@/stores/auth";

vi.mock("@/api/account/orders", () => ({
    listAccountOrders: vi.fn(),
    getAccountOrdersSummary: vi.fn(),
}));

const getAccountOrdersSummaryMock = getAccountOrdersSummary as unknown as ReturnType<typeof vi.fn>;

const setupAuthenticatedUser = () => {
    const authStore = useAuthStore();
    authStore.user = {
        id: 1,
        first_name: "Jane",
        last_name: "Doe",
        name: "Jane Doe",
        email: "jane@example.com",
        phone: "+15551234567",
        roles: ["customer"],
        is_email_verified: true,
    };

    return authStore;
};

beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
});

describe("useAccountProfile", () => {
    it("loads profile info and account metrics", async () => {
        const authStore = setupAuthenticatedUser();
        authStore.ensureUserLoaded = vi.fn(async () => {}) as typeof authStore.ensureUserLoaded;
        getAccountOrdersSummaryMock.mockResolvedValue({
            total_orders: 8,
            paid_orders: 5,
            in_delivery_orders: 2,
            total_spent: 999.5,
        });

        const scope = effectScope();
        const api = scope.run(() => useAccountProfile());

        expect(api).not.toBeNull();
        if (!api) {
            scope.stop();
            return;
        }

        await api.loadProfile();

        expect(authStore.ensureUserLoaded).toHaveBeenCalledTimes(1);
        expect(api.form.first_name).toBe("Jane");
        expect(api.form.last_name).toBe("Doe");
        expect(api.form.phone).toBe("+15551234567");
        expect(api.metrics.totalOrders).toBe(8);
        expect(api.metrics.paidOrders).toBe(5);
        expect(api.metrics.inDelivery).toBe(2);
        expect(api.metrics.loadedTotalSpent).toBe(999.5);
        expect(api.formatPrice(10)).toBe("$10.00");

        scope.stop();
    });

    it("updates profile and shows error on failed update", async () => {
        const authStore = setupAuthenticatedUser();
        authStore.updateProfile = vi.fn(
            async ({
                first_name,
                last_name,
                phone,
            }: {
                first_name: string;
                last_name: string;
                phone: string | null;
            }) => {
                authStore.user = {
                    ...authStore.user!,
                    first_name,
                    last_name,
                    phone,
                    name: `${first_name} ${last_name}`.trim(),
                };
            },
        ) as typeof authStore.updateProfile;
        getAccountOrdersSummaryMock.mockResolvedValue({
            total_orders: 0,
            paid_orders: 0,
            in_delivery_orders: 0,
            total_spent: 0,
        });

        const scope = effectScope();
        const api = scope.run(() => useAccountProfile());

        expect(api).not.toBeNull();
        if (!api) {
            scope.stop();
            return;
        }

        await api.loadProfile();
        api.form.first_name = "Alice";
        api.form.last_name = "Smith";
        api.form.phone = "";

        await api.submitProfileUpdate();

        expect(authStore.updateProfile).toHaveBeenCalledWith({
            first_name: "Alice",
            last_name: "Smith",
            phone: null,
        });
        expect(api.profileNotice.type).toBe("success");
        expect(api.profileNotice.message).toBe("Profile updated successfully.");
        expect(api.profileName.value).toBe("Alice Smith");

        authStore.updateProfile = vi.fn(async () => {
            throw new Error("Update failed");
        }) as typeof authStore.updateProfile;
        api.form.first_name = "Bob";

        await api.submitProfileUpdate();

        expect(api.profileNotice.type).toBe("error");
        expect(api.profileNotice.message).toBe("Update failed");

        scope.stop();
    });
});
