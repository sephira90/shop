import { ref, type Ref } from "vue";

import {
    createPromotion as createPromotionRequest,
    deletePromotion as deletePromotionRequest,
    updatePromotion as updatePromotionRequest,
} from "@/api/admin/promotions";
import { executeAdminDeleteMutationPipeline } from "@/composables/admin/adminDeleteMutationPipeline";
import { resolvePageAfterLastItemRemoval } from "@/composables/admin/adminListPagination";
import { executeAdminSubmitMutationPipeline } from "@/composables/admin/adminSubmitMutationPipeline";
import type { AdminUiEffectsAdapter } from "@/composables/admin/adminUiEffects";
import type { AdminSuccessNoticeAdapter } from "@/composables/admin/useAdminMutationContext";
import type { ExecuteAdminMutation } from "@/composables/useAdminMutation";
import type { Promotion, PromotionFormState } from "@/types/admin-promotions";
import { buildPromotionMutationPayload } from "@/validators/admin/promotions";

interface AdminPromotionsCrudQueryAdapter {
    promotions: Ref<Promotion[]>;
    page: Ref<number>;
    loadPromotions: (targetPage?: number) => Promise<void>;
    selectedPromotionId: Ref<number | null>;
}

interface AdminPromotionsCrudFormAdapter {
    editingPromotionId: Ref<number | null>;
    promotionForm: Ref<PromotionFormState>;
    resetPromotionFormKeepNotice: () => void;
}

interface UseAdminPromotionCrudMutationsOptions {
    query: AdminPromotionsCrudQueryAdapter;
    formState: AdminPromotionsCrudFormAdapter;
    executeMutation: ExecuteAdminMutation;
    notice: AdminSuccessNoticeAdapter;
    uiEffects: AdminUiEffectsAdapter;
}

export const useAdminPromotionCrudMutations = ({
    query,
    formState,
    executeMutation,
    notice,
    uiEffects,
}: UseAdminPromotionCrudMutationsOptions) => {
    const isSubmittingPromotion = ref(false);
    const isDeletingPromotionId = ref<number | null>(null);

    const submitPromotion = async (): Promise<void> => {
        await executeAdminSubmitMutationPipeline({
            executeMutation,
            setPending: (pending) => {
                isSubmittingPromotion.value = pending;
            },
            errorMessage: "Unable to save promotion.",
            buildPayload: () =>
                buildPromotionMutationPayload(
                    formState.promotionForm.value,
                    formState.editingPromotionId.value !== null,
                ),
            editingId: formState.editingPromotionId.value,
            runCreate: async (payload) => {
                await createPromotionRequest(payload);
            },
            runUpdate: async (id, payload) => {
                await updatePromotionRequest(id, payload);
            },
            showSuccess: notice.showSuccess,
            successMessages: {
                create: "Campaign created.",
                update: "Campaign updated.",
            },
            onCreateSuccess: async () => {
                await query.loadPromotions(1);
            },
            onUpdateSuccess: async (id) => {
                await query.loadPromotions(query.page.value);
                query.selectedPromotionId.value = id;
            },
            onSuccess: async () => {
                formState.resetPromotionFormKeepNotice();
            },
        });
    };

    const removePromotion = async (promotion: Promotion): Promise<void> => {
        await executeAdminDeleteMutationPipeline<Promotion>({
            item: promotion,
            executeMutation,
            confirm: uiEffects.confirm,
            confirmMessage: (item) => `Delete campaign "${item.name}"?`,
            setPending: (pending) => {
                isDeletingPromotionId.value = pending ? promotion.id : null;
            },
            errorMessage: "Unable to delete campaign.",
            runDelete: async (item) => {
                await deletePromotionRequest(item.id);
            },
            showSuccess: notice.showSuccess,
            successMessage: "Campaign deleted.",
            onDeleted: async (item) => {
                const nextPage = resolvePageAfterLastItemRemoval({
                    currentPage: query.page.value,
                    visibleItemsCount: query.promotions.value.length,
                });
                await query.loadPromotions(nextPage);
                if (formState.editingPromotionId.value === item.id) {
                    formState.resetPromotionFormKeepNotice();
                }
            },
        });
    };

    return {
        isSubmittingPromotion,
        isDeletingPromotionId,
        submitPromotion,
        removePromotion,
    };
};
