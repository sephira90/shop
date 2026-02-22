import { reactive, ref } from "vue";

import { useApiError } from "@/composables/useApiError";

import type { useAccountProfileQuery } from "./useAccountProfileQuery";

interface UseAccountProfileMutationsOptions {
    query: ReturnType<typeof useAccountProfileQuery>;
}

export const useAccountProfileMutations = ({ query }: UseAccountProfileMutationsOptions) => {
    const { parseApiError } = useApiError();
    const isSavingProfile = ref(false);
    const profileNotice = reactive({
        type: "success" as "success" | "error",
        message: "",
    });

    const resetProfileForm = (): void => {
        query.fillProfileForm();
        profileNotice.message = "";
    };

    const submitProfileUpdate = async (): Promise<void> => {
        profileNotice.message = "";
        isSavingProfile.value = true;

        try {
            await query.authStore.updateProfile({
                first_name: query.form.first_name.trim(),
                last_name: query.form.last_name.trim(),
                phone: query.form.phone.trim() === "" ? null : query.form.phone.trim(),
            });
            query.fillProfileForm();
            profileNotice.type = "success";
            profileNotice.message = "Profile updated successfully.";
        } catch (error: unknown) {
            profileNotice.type = "error";
            profileNotice.message = parseApiError(error, "Unable to update profile.");
        } finally {
            isSavingProfile.value = false;
        }
    };

    return {
        isSavingProfile,
        profileNotice,
        resetProfileForm,
        submitProfileUpdate,
    };
};
