import { computed, reactive } from "vue";

import { getAccountOrdersSummary } from "@/api/account/orders";
import { useAuthStore } from "@/stores/auth";
import {
    verificationStatusTone as resolveVerificationStatusTone,
    type StatusTone,
} from "@/utils/order-presentation";

export const useAccountProfileQuery = () => {
    const authStore = useAuthStore();
    const metrics = reactive({
        totalOrders: 0,
        paidOrders: 0,
        inDelivery: 0,
        loadedTotalSpent: 0,
    });
    const form = reactive({
        first_name: "",
        last_name: "",
        phone: "",
    });

    const profileName = computed<string>(() => authStore.user?.name?.trim() || "Customer");
    const profileEmail = computed<string>(() => authStore.user?.email ?? "Unknown");
    const profilePhone = computed<string>(() => authStore.user?.phone?.trim() || "Not provided");
    const profileInitial = computed<string>(() => profileName.value.charAt(0).toUpperCase());
    const verificationLabel = computed<string>(() =>
        authStore.user?.is_email_verified ? "Email verified" : "Email pending",
    );
    const verificationTone = computed<StatusTone>(() =>
        resolveVerificationStatusTone(Boolean(authStore.user?.is_email_verified)),
    );
    const roleLabels = computed<string[]>(() => {
        const roles = authStore.user?.roles ?? [];

        if (roles.length === 0) {
            return ["Guest"];
        }

        return roles.map((role) => role.charAt(0).toUpperCase() + role.slice(1));
    });

    const fillProfileForm = (): void => {
        const user = authStore.user;
        if (!user) {
            return;
        }

        const fallbackNameParts = (user.name ?? "").trim().split(/\s+/).filter(Boolean);
        form.first_name = (user.first_name ?? fallbackNameParts[0] ?? "").trim();
        form.last_name = (user.last_name ?? fallbackNameParts.slice(1).join(" ") ?? "").trim();
        form.phone = (user.phone ?? "").trim();
    };

    const loadOrderMetrics = async (): Promise<void> => {
        try {
            const summary = await getAccountOrdersSummary();

            metrics.totalOrders = summary.total_orders;
            metrics.paidOrders = summary.paid_orders;
            metrics.inDelivery = summary.in_delivery_orders;
            metrics.loadedTotalSpent = summary.total_spent;
        } catch {
            metrics.totalOrders = 0;
            metrics.paidOrders = 0;
            metrics.inDelivery = 0;
            metrics.loadedTotalSpent = 0;
        }
    };

    return {
        authStore,
        metrics,
        form,
        profileName,
        profileEmail,
        profilePhone,
        profileInitial,
        verificationLabel,
        verificationTone,
        roleLabels,
        fillProfileForm,
        loadOrderMetrics,
    };
};
