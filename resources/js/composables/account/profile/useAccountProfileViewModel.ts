import { formatMoney } from "@/utils/order-presentation";

import { useAccountProfileMutations } from "./useAccountProfileMutations";
import { useAccountProfileQuery } from "./useAccountProfileQuery";

export const useAccountProfileViewModel = () => {
    const query = useAccountProfileQuery();
    const mutations = useAccountProfileMutations({
        query,
    });

    const formatPrice = (value: number): string => formatMoney(value, "USD");

    const loadProfile = async (): Promise<void> => {
        await query.authStore.ensureUserLoaded();
        query.fillProfileForm();
        await query.loadOrderMetrics();
    };

    return {
        authStore: query.authStore,
        isSavingProfile: mutations.isSavingProfile,
        metrics: query.metrics,
        form: query.form,
        profileNotice: mutations.profileNotice,
        profileName: query.profileName,
        profileEmail: query.profileEmail,
        profilePhone: query.profilePhone,
        profileInitial: query.profileInitial,
        verificationLabel: query.verificationLabel,
        verificationTone: query.verificationTone,
        roleLabels: query.roleLabels,
        resetProfileForm: mutations.resetProfileForm,
        formatPrice,
        submitProfileUpdate: mutations.submitProfileUpdate,
        loadProfile,
    };
};
