import type { AdminRouteSyncOptions } from "@/composables/admin/adminRouteSync";
import type { AdminQueryNoticeAdapter } from "@/composables/admin/useAdminMutationContext";

import { useAdminOrderDetailsState, type StatusDraft } from "./useAdminOrderDetailsState";
import { useAdminOrdersDerivedState } from "./useAdminOrdersDerivedState";
import { useAdminOrdersFilterState } from "./useAdminOrdersFilterState";
import { useAdminOrdersListState } from "./useAdminOrdersListState";

export type { StatusDraft };

interface UseAdminOrdersQueryOptions {
    notice: AdminQueryNoticeAdapter;
    routeSync?: AdminRouteSyncOptions;
}

export const useAdminOrdersQuery = ({ notice, routeSync }: UseAdminOrdersQueryOptions) => {
    const filterState = useAdminOrdersFilterState(routeSync);
    const detailState = useAdminOrderDetailsState({
        showApiError: notice.showApiError,
    });
    const { orders, page, isLoading, meta, loadOrders } = useAdminOrdersListState({
        notice,
        filterState,
        detailState,
        routeSync,
    });

    const derivedState = useAdminOrdersDerivedState({
        orders,
        selectedOrderId: detailState.selectedOrderId,
    });

    return {
        filters: filterState.filters,
        orders,
        page,
        isLoading,
        meta,
        loadOrders,
        selectedOrderId: detailState.selectedOrderId,
        isDetailLoading: detailState.isDetailLoading,
        orderDetails: detailState.orderDetails,
        selectedOrderDetail: detailState.selectedOrderDetail,
        statusDrafts: detailState.statusDrafts,
        ensureDraft: detailState.ensureDraft,
        syncDraftWithOrder: detailState.syncDraftWithOrder,
        loadOrderDetail: detailState.loadOrderDetail,
        selectOrder: detailState.selectOrder,
        filteredOrders: derivedState.filteredOrders,
        selectedOrderSummary: derivedState.selectedOrderSummary,
        currentDraft: detailState.currentDraft,
        paidCount: derivedState.paidCount,
        completedCount: derivedState.completedCount,
        pendingPaymentCount: derivedState.pendingPaymentCount,
    };
};
