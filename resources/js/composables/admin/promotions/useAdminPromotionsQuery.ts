import { computed, ref } from "vue";

import type { ListResponse } from "@/api/response";
import { listPromotions } from "@/api/admin/promotions";
import type { AdminRouteSyncOptions } from "@/composables/admin/adminRouteSync";
import { useRouteSyncedPagination } from "@/composables/useRouteSyncedPagination";
import { useServerPaginatedList } from "@/composables/useServerPaginatedList";
import { useServerListFilters } from "@/composables/useServerListFilters";
import {
    buildAdminPromotionListParams,
    buildAdminPromotionRouteQuery,
    isSameAdminPromotionRouteQuery,
    parseAdminPromotionFiltersFromRouteQuery,
} from "@/queries/admin/promotions";
import type {
    Promotion,
    PromotionListParams,
    PromotionStatusFilter,
} from "@/types/admin-promotions";

interface AdminPromotionsQueryNoticeAdapter {
    clearNotice: () => void;
    showApiError: (error: unknown, fallback: string) => void;
}

export const useAdminPromotionsQuery = (
    notice: AdminPromotionsQueryNoticeAdapter,
    routeSync?: AdminRouteSyncOptions,
) => {
    const initialFilters = routeSync
        ? parseAdminPromotionFiltersFromRouteQuery(routeSync.route.query)
        : { searchQuery: "", statusFilter: "all" as PromotionStatusFilter, page: 1 };
    const searchQuery = ref(initialFilters.searchQuery);
    const statusFilter = ref<PromotionStatusFilter>(initialFilters.statusFilter);
    const selectedPromotionId = ref<number | null>(null);

    const {
        items: promotions,
        page,
        isLoading,
        meta,
        load: loadPromotionsRaw,
    } = useServerPaginatedList<Promotion, PromotionListParams>({
        buildParams: (targetPage) =>
            buildAdminPromotionListParams(targetPage, {
                searchQuery: searchQuery.value,
                statusFilter: statusFilter.value,
            }),
        fetchPage: listPromotions,
        ...(routeSync
            ? { initialPage: initialFilters.page }
            : {
                  filterSource: () => [searchQuery.value, statusFilter.value],
                  debounceMs: 300,
              }),
        resetOnError: true,
        onLoading: () => {
            notice.clearNotice();
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
            notice.showApiError(error, "Unable to load promotions.");
        },
    });
    const routePagination = useRouteSyncedPagination({
        route: routeSync?.route,
        router: routeSync?.router,
        parseRouteQuery: parseAdminPromotionFiltersFromRouteQuery,
        buildRouteQuery: buildAdminPromotionRouteQuery,
        isSameRouteQuery: isSameAdminPromotionRouteQuery,
        applyParsedFilters: (parsed) => {
            searchQuery.value = parsed.searchQuery;
            statusFilter.value = parsed.statusFilter;
            page.value = parsed.page;
        },
        readFiltersForPage: (targetPage) => ({
            searchQuery: searchQuery.value,
            statusFilter: statusFilter.value,
            page: targetPage,
        }),
        fetchPage: loadPromotionsRaw,
        immediate: false,
    });
    const loadPromotions = async (targetPage = page.value): Promise<void> => {
        await routePagination.load(targetPage);
    };

    if (routeSync) {
        useServerListFilters(
            () => [searchQuery.value, statusFilter.value],
            () => loadPromotions(1),
            {
                debounceMs: 300,
            },
        );
    }

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

    return {
        promotions,
        page,
        isLoading,
        meta,
        searchQuery,
        statusFilter,
        selectedPromotionId,
        filteredPromotions,
        selectedPromotion,
        loadPromotions,
        selectPromotion,
    };
};
