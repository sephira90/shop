import { computed, ref } from "vue";

import {
    createPromotion as createPromotionRequest,
    createPromotionCoupon,
    deletePromotion as deletePromotionRequest,
    listPromotions,
    updateCoupon as updateCouponRequest,
    updatePromotion as updatePromotionRequest,
} from "@/api/admin/promotions";
import type { ListResponse } from "@/api/response";
import { useAdminMutation } from "@/composables/useAdminMutation";
import { useAdminNotice } from "@/composables/useAdminNotice";
import { useServerPaginatedList } from "@/composables/useServerPaginatedList";
import { buildAdminPromotionListParams } from "@/queries/admin/promotions";
import type {
    Coupon,
    CouponFormState,
    Promotion,
    PromotionFormState,
    PromotionListParams,
    PromotionStatusFilter,
} from "@/types/admin-promotions";
import { normalizeDatetimeForInput } from "@/utils/datetime";
import {
    buildCouponCreatePayload,
    buildPromotionMutationPayload,
    createCouponFormState,
    createPromotionFormState,
} from "@/validators/admin/promotions";

export const useAdminPromotions = () => {
    const searchQuery = ref("");
    const statusFilter = ref<PromotionStatusFilter>("all");
    const { notice, clearNotice, showSuccess, showError, showApiError } = useAdminNotice();
    const { executeMutation } = useAdminMutation({
        clearNotice,
        showApiError,
    });
    const isSubmittingPromotion = ref(false);
    const isSubmittingCoupon = ref(false);
    const isDeletingPromotionId = ref<number | null>(null);
    const updatingCouponId = ref<number | null>(null);
    const editingPromotionId = ref<number | null>(null);
    const selectedPromotionId = ref<number | null>(null);
    const promotionForm = ref<PromotionFormState>(createPromotionFormState());
    const couponForm = ref<CouponFormState>(createCouponFormState());

    const {
        items: promotions,
        page,
        isLoading,
        meta,
        load: loadPromotions,
    } = useServerPaginatedList<Promotion, PromotionListParams>({
        buildParams: (targetPage) =>
            buildAdminPromotionListParams(targetPage, {
                searchQuery: searchQuery.value,
                statusFilter: statusFilter.value,
            }),
        fetchPage: listPromotions,
        filterSource: () => [searchQuery.value, statusFilter.value],
        debounceMs: 300,
        resetOnError: true,
        onLoading: () => {
            clearNotice();
        },
        onLoaded: (response: ListResponse<Promotion>) => {
            if (
                !selectedPromotionId.value ||
                !response.data.some((promotion) => promotion.id === selectedPromotionId.value)
            ) {
                selectedPromotionId.value = response.data[0]?.id ?? null;
            }
        },
        onError: (error: unknown) => {
            selectedPromotionId.value = null;
            showApiError(error, "Unable to load promotions.");
        },
    });

    const filteredPromotions = computed<Promotion[]>(() => promotions.value);

    const selectedPromotion = computed<Promotion | null>(() => {
        if (selectedPromotionId.value === null) {
            return null;
        }

        return (
            promotions.value.find((promotion) => promotion.id === selectedPromotionId.value) ?? null
        );
    });

    const selectPromotion = (promotionId: number): void => {
        selectedPromotionId.value = promotionId;
    };

    const clearPromotionFormState = (): void => {
        editingPromotionId.value = null;
        promotionForm.value = createPromotionFormState();
    };

    const resetPromotionForm = (): void => {
        clearPromotionFormState();
        clearNotice();
    };

    const resetPromotionFormKeepNotice = (): void => {
        clearPromotionFormState();
    };

    const startEditPromotion = (promotion: Promotion): void => {
        editingPromotionId.value = promotion.id;
        selectedPromotionId.value = promotion.id;
        promotionForm.value = {
            name: promotion.name,
            code: promotion.code ?? "",
            type: promotion.type,
            value: Number(promotion.value),
            is_active: promotion.is_active,
            starts_at: normalizeDatetimeForInput(promotion.starts_at),
            ends_at: normalizeDatetimeForInput(promotion.ends_at),
            usage_limit: promotion.usage_limit !== null ? String(promotion.usage_limit) : "",
            coupon_is_active: true,
            coupon_max_redemptions: "",
            coupon_expires_at: "",
        };
        clearNotice();
        window.scrollTo({ top: 0, behavior: "smooth" });
    };

    const submitPromotion = async (): Promise<void> => {
        await executeMutation<void>({
            setPending: (pending) => {
                isSubmittingPromotion.value = pending;
            },
            errorMessage: "Unable to save promotion.",
            run: async () => {
                const payload = buildPromotionMutationPayload(
                    promotionForm.value,
                    editingPromotionId.value !== null,
                );

                if (editingPromotionId.value !== null) {
                    const promotionId = editingPromotionId.value;
                    await updatePromotionRequest(promotionId, payload);
                    showSuccess("Campaign updated.");
                    await loadPromotions(page.value);
                    resetPromotionFormKeepNotice();
                    selectedPromotionId.value = promotionId;
                } else {
                    await createPromotionRequest(payload);
                    showSuccess("Campaign created.");
                    await loadPromotions(1);
                    resetPromotionFormKeepNotice();
                }
            },
        });
    };

    const removePromotion = async (promotion: Promotion): Promise<void> => {
        if (!window.confirm(`Delete campaign "${promotion.name}"?`)) {
            return;
        }

        await executeMutation<void>({
            setPending: (pending) => {
                isDeletingPromotionId.value = pending ? promotion.id : null;
            },
            errorMessage: "Unable to delete campaign.",
            run: async () => {
                await deletePromotionRequest(promotion.id);
                showSuccess("Campaign deleted.");
                const nextPage =
                    promotions.value.length === 1 && page.value > 1 ? page.value - 1 : page.value;
                await loadPromotions(nextPage);
                if (editingPromotionId.value === promotion.id) {
                    resetPromotionFormKeepNotice();
                }
            },
        });
    };

    const createCoupon = async (): Promise<void> => {
        const promotion = selectedPromotion.value;

        if (!promotion) {
            showError("Select promotion first.");
            return;
        }

        await executeMutation<void>({
            setPending: (pending) => {
                isSubmittingCoupon.value = pending;
            },
            errorMessage: "Unable to create coupon.",
            run: async () => {
                await createPromotionCoupon(
                    promotion.id,
                    buildCouponCreatePayload(couponForm.value),
                );
                showSuccess("Coupon created.");
                couponForm.value = createCouponFormState();
                await loadPromotions(page.value);
            },
        });
    };

    const toggleCoupon = async (coupon: Coupon): Promise<void> => {
        await executeMutation<void>({
            setPending: (pending) => {
                updatingCouponId.value = pending ? coupon.id : null;
            },
            errorMessage: "Unable to update coupon.",
            run: async () => {
                await updateCouponRequest(coupon.id, {
                    is_active: !coupon.is_active,
                });
                showSuccess("Coupon status updated.");
                await loadPromotions(page.value);
            },
        });
    };

    return {
        promotions,
        page,
        isLoading,
        isSubmittingPromotion,
        isSubmittingCoupon,
        isDeletingPromotionId,
        updatingCouponId,
        editingPromotionId,
        selectedPromotionId,
        searchQuery,
        statusFilter,
        meta,
        notice,
        promotionForm,
        couponForm,
        filteredPromotions,
        selectedPromotion,
        loadPromotions,
        selectPromotion,
        resetPromotionForm,
        startEditPromotion,
        submitPromotion,
        removePromotion,
        createCoupon,
        toggleCoupon,
    };
};
