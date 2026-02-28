import { ref } from "vue";

import type { AdminRouteSyncOptions } from "@/composables/admin/adminRouteSync";
import {
    buildAdminPromotionListParams,
    parseAdminPromotionFiltersFromRouteQuery,
    type AdminPromotionRouteFilters,
} from "@/queries/admin/promotions";
import type { PromotionListParams, PromotionStatusFilter } from "@/types/admin-promotions";

type PromotionFilterSourceTuple = [string, PromotionStatusFilter];

export const useAdminPromotionsFilterState = (routeSync?: AdminRouteSyncOptions) => {
    const initialRouteFilters = routeSync
        ? parseAdminPromotionFiltersFromRouteQuery(routeSync.route.query)
        : { searchQuery: "", statusFilter: "all" as PromotionStatusFilter, page: 1 };
    const searchQuery = ref(initialRouteFilters.searchQuery);
    const statusFilter = ref<PromotionStatusFilter>(initialRouteFilters.statusFilter);

    const buildListParams = (targetPage: number): PromotionListParams =>
        buildAdminPromotionListParams(targetPage, {
            searchQuery: searchQuery.value,
            statusFilter: statusFilter.value,
        });

    const filterSource = (): PromotionFilterSourceTuple => [searchQuery.value, statusFilter.value];

    const applyParsedFilters = (parsed: AdminPromotionRouteFilters): number => {
        searchQuery.value = parsed.searchQuery;
        statusFilter.value = parsed.statusFilter;

        return parsed.page;
    };

    const readFiltersForPage = (targetPage: number): AdminPromotionRouteFilters => ({
        searchQuery: searchQuery.value,
        statusFilter: statusFilter.value,
        page: targetPage,
    });

    return {
        initialPage: initialRouteFilters.page,
        searchQuery,
        statusFilter,
        buildListParams,
        filterSource,
        applyParsedFilters,
        readFiltersForPage,
    };
};
