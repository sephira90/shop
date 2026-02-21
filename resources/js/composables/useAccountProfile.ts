import { computed, reactive, ref } from "vue";

import { getAccountOrdersSummary } from "@/api/account/orders";
import { useApiError } from "@/composables/useApiError";
import { useAuthStore } from "@/stores/auth";

export const useAccountProfile = () => {
    const authStore = useAuthStore();
    const { parseApiError } = useApiError();
    const isSavingProfile = ref(false);
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
    const profileNotice = reactive({
        type: "success" as "success" | "error",
        message: "",
    });

    const profileName = computed<string>(() => authStore.user?.name?.trim() || "Customer");
    const profileEmail = computed<string>(() => authStore.user?.email ?? "Unknown");
    const profilePhone = computed<string>(() => authStore.user?.phone?.trim() || "Not provided");
    const profileInitial = computed<string>(() => profileName.value.charAt(0).toUpperCase());
    const verificationLabel = computed<string>(() =>
        authStore.user?.is_email_verified ? "Email verified" : "Email pending",
    );
    const verificationClass = computed<string>(() =>
        authStore.user?.is_email_verified ? "status-chip--good" : "status-chip--warn",
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

    const resetProfileForm = (): void => {
        fillProfileForm();
        profileNotice.message = "";
    };

    const formatPrice = (value: number): string => {
        return new Intl.NumberFormat("en-US", {
            style: "currency",
            currency: "USD",
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(value);
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

    const submitProfileUpdate = async (): Promise<void> => {
        profileNotice.message = "";
        isSavingProfile.value = true;

        try {
            await authStore.updateProfile({
                first_name: form.first_name.trim(),
                last_name: form.last_name.trim(),
                phone: form.phone.trim() === "" ? null : form.phone.trim(),
            });
            fillProfileForm();
            profileNotice.type = "success";
            profileNotice.message = "Profile updated successfully.";
        } catch (error: unknown) {
            profileNotice.type = "error";
            profileNotice.message = parseApiError(error, "Unable to update profile.");
        } finally {
            isSavingProfile.value = false;
        }
    };

    const loadProfile = async (): Promise<void> => {
        await authStore.ensureUserLoaded();
        fillProfileForm();
        await loadOrderMetrics();
    };

    return {
        authStore,
        isSavingProfile,
        metrics,
        form,
        profileNotice,
        profileName,
        profileEmail,
        profilePhone,
        profileInitial,
        verificationLabel,
        verificationClass,
        roleLabels,
        resetProfileForm,
        formatPrice,
        submitProfileUpdate,
        loadProfile,
    };
};
