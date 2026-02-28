import { computed } from "vue";

import type { AdminRouteSyncOptions } from "@/composables/admin/adminRouteSync";
import type { AdminQueryNoticeAdapter } from "@/composables/admin/useAdminMutationContext";
import type { Promotion } from "@/types/admin-promotions";

import { useAdminPromotionsFilterState } from "./useAdminPromotionsFilterState";
import { useAdminPromotionsListState } from "./useAdminPromotionsListState";
import { useAdminPromotionsSelectionState } from "./useAdminPromotionsSelectionState";

export const useAdminPromotionsQuery = (
    notice: AdminQueryNoticeAdapter,
    routeSync?: AdminRouteSyncOptions,
) => {
    const filterState = useAdminPromotionsFilterState(routeSync);
    const selectionState = useAdminPromotionsSelectionState();
    const { promotions, page, isLoading, meta, loadPromotions } = useAdminPromotionsListState({
        notice,
        filterState,
        selectionState,
        routeSync,
    });

    const filteredPromotions = computed<Promotion[]>(() => promotions.value);

    const selectedPromotion = computed<Promotion | null>(() => {
        if (selectionState.selectedPromotionId.value === null) {
            return null;
        }

        return (
            promotions.value.find(
                (promotion) => promotion.id === selectionState.selectedPromotionId.value,
            ) ?? null
        );
    });

    return {
        promotions,
        page,
        isLoading,
        meta,
        searchQuery: filterState.searchQuery,
        statusFilter: filterState.statusFilter,
        selectedPromotionId: selectionState.selectedPromotionId,
        filteredPromotions,
        selectedPromotion,
        loadPromotions,
        selectPromotion: selectionState.selectPromotion,
    };
};
