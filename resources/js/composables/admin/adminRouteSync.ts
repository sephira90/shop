import type { Ref, WatchSource } from "vue";
import type { LocationQueryRaw } from "vue-router";

import { useRouteSyncedPagination } from "@/composables/useRouteSyncedPagination";
import { useServerListFilters } from "@/composables/useServerListFilters";
import type { RouteQueryLike, RouteQueryRouterLike } from "@/composables/useRouteSyncedPagination";

export interface AdminRouteSyncOptions {
    route: RouteQueryLike;
    router: RouteQueryRouterLike;
}

interface UseAdminRouteSyncedLoaderOptions<TFilters extends { page: number }> {
    page: Ref<number>;
    fetchPage: (page: number) => Promise<void>;
    parseRouteQuery: (query: Readonly<Record<string, unknown>>) => TFilters;
    buildRouteQuery: (filters: TFilters) => LocationQueryRaw;
    isSameRouteQuery: (
        left: Readonly<Record<string, unknown>>,
        right: Readonly<Record<string, unknown>>,
    ) => boolean;
    applyParsedFilters: (filters: TFilters) => void;
    readFiltersForPage: (page: number) => TFilters;
    filterSource: WatchSource<unknown> | WatchSource<unknown>[];
    routeSync?: AdminRouteSyncOptions;
    debounceMs?: number;
}

export const useAdminRouteSyncedLoader = <TFilters extends { page: number }>(
    options: UseAdminRouteSyncedLoaderOptions<TFilters>,
) => {
    let suppressedFilterQuery: LocationQueryRaw | null = null;

    const routePagination = useRouteSyncedPagination({
        route: options.routeSync?.route,
        router: options.routeSync?.router,
        parseRouteQuery: options.parseRouteQuery,
        buildRouteQuery: options.buildRouteQuery,
        isSameRouteQuery: options.isSameRouteQuery,
        applyParsedFilters: (filters) => {
            options.applyParsedFilters(filters);
            suppressedFilterQuery = options.buildRouteQuery(options.readFiltersForPage(1));
        },
        readFiltersForPage: options.readFiltersForPage,
        fetchPage: options.fetchPage,
        immediate: false,
    });

    const load = async (targetPage = options.page.value): Promise<void> => {
        await routePagination.load(targetPage);
    };

    if (options.routeSync) {
        useServerListFilters(options.filterSource, () => load(1), {
            debounceMs: options.debounceMs ?? 300,
            shouldReload: () => {
                if (!suppressedFilterQuery) {
                    return true;
                }

                const currentFilterQuery = options.buildRouteQuery(options.readFiltersForPage(1));
                const shouldSkip = options.isSameRouteQuery(
                    currentFilterQuery,
                    suppressedFilterQuery,
                );
                suppressedFilterQuery = null;

                return !shouldSkip;
            },
        });
    }

    return {
        load,
    };
};
