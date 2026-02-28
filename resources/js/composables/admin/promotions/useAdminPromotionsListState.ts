import type { ListResponse } from "@/api/response";
import { listPromotions } from "@/api/admin/promotions";
import {
    type AdminRouteSyncOptions,
    useAdminRouteSyncedLoader,
} from "@/composables/admin/adminRouteSync";
import type { AdminQueryNoticeAdapter } from "@/composables/admin/useAdminMutationContext";
import { useServerPaginatedList } from "@/composables/useServerPaginatedList";
import {
    buildAdminPromotionRouteQuery,
    isSameAdminPromotionRouteQuery,
    parseAdminPromotionFiltersFromRouteQuery,
    type AdminPromotionRouteFilters,
} from "@/queries/admin/promotions";
import type {
    Promotion,
    PromotionListParams,
    PromotionStatusFilter,
} from "@/types/admin-promotions";

type PromotionFilterSourceTuple = [string, PromotionStatusFilter];

interface AdminPromotionsListFilterAdapter {
    initialPage: number;
    buildListParams: (targetPage: number) => PromotionListParams;
    filterSource: () => PromotionFilterSourceTuple;
    applyParsedFilters: (parsed: AdminPromotionRouteFilters) => number;
    readFiltersForPage: (targetPage: number) => AdminPromotionRouteFilters;
}

interface AdminPromotionsSelectionAdapter {
    syncSelectionWithPromotions: (promotions: readonly Promotion[]) => void;
    clearSelection: () => void;
}

interface UseAdminPromotionsListStateOptions {
    notice: AdminQueryNoticeAdapter;
    filterState: AdminPromotionsListFilterAdapter;
    selectionState: AdminPromotionsSelectionAdapter;
    routeSync?: AdminRouteSyncOptions;
}

export const useAdminPromotionsListState = ({
    notice,
    filterState,
    selectionState,
    routeSync,
}: UseAdminPromotionsListStateOptions) => {
    const {
        items: promotions,
        page,
        isLoading,
        meta,
        load: loadPromotionsRaw,
    } = useServerPaginatedList<Promotion, PromotionListParams>({
        buildParams: filterState.buildListParams,
        fetchPage: listPromotions,
        ...(routeSync
            ? { initialPage: filterState.initialPage }
            : {
                  filterSource: filterState.filterSource,
                  debounceMs: 300,
              }),
        resetOnError: true,
        onLoading: () => {
            notice.clearNotice();
        },
        onLoaded: (response: ListResponse<Promotion>) => {
            selectionState.syncSelectionWithPromotions(response.data);
        },
        onError: (error: unknown) => {
            selectionState.clearSelection();
            notice.showApiError(error, "Unable to load promotions.");
        },
    });

    const { load: loadPromotions } = useAdminRouteSyncedLoader({
        routeSync,
        page,
        fetchPage: loadPromotionsRaw,
        parseRouteQuery: parseAdminPromotionFiltersFromRouteQuery,
        buildRouteQuery: buildAdminPromotionRouteQuery,
        isSameRouteQuery: isSameAdminPromotionRouteQuery,
        applyParsedFilters: (parsed) => {
            page.value = filterState.applyParsedFilters(parsed);
        },
        readFiltersForPage: filterState.readFiltersForPage,
        filterSource: filterState.filterSource,
        debounceMs: 300,
    });

    return {
        promotions,
        page,
        isLoading,
        meta,
        loadPromotions,
    };
};
