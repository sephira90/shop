import { watch } from "vue";
import type { LocationQueryRaw, RouteLocationRaw } from "vue-router";

export interface RouteQueryLike {
    query: Readonly<Record<string, unknown>>;
}

export interface RouteQueryRouterLike {
    replace(to: RouteLocationRaw): Promise<unknown> | unknown;
}

interface UseRouteSyncedPaginationOptions<TFilters extends { page: number }> {
    route?: RouteQueryLike;
    router?: RouteQueryRouterLike;
    parseRouteQuery: (query: Readonly<Record<string, unknown>>) => TFilters;
    buildRouteQuery: (filters: TFilters) => LocationQueryRaw;
    isSameRouteQuery: (
        left: Readonly<Record<string, unknown>>,
        right: Readonly<Record<string, unknown>>,
    ) => boolean;
    applyParsedFilters: (filters: TFilters) => void;
    readFiltersForPage: (page: number) => TFilters;
    fetchPage: (page: number) => Promise<void>;
    immediate?: boolean;
}

export const useRouteSyncedPagination = <TFilters extends { page: number }>(
    options: UseRouteSyncedPaginationOptions<TFilters>,
) => {
    const hasRouteSync = options.route !== undefined && options.router !== undefined;

    const load = async (targetPage: number): Promise<void> => {
        const nextPage = Number.isInteger(targetPage) && targetPage > 0 ? targetPage : 1;

        if (!hasRouteSync || !options.route || !options.router) {
            await options.fetchPage(nextPage);
            return;
        }

        const nextQuery = options.buildRouteQuery(options.readFiltersForPage(nextPage));

        if (options.isSameRouteQuery(options.route.query, nextQuery)) {
            await options.fetchPage(nextPage);
            return;
        }

        await options.router.replace({
            query: nextQuery,
        });
    };

    const route = options.route;

    if (hasRouteSync && route) {
        watch(
            () => route.query,
            (query) => {
                const parsed = options.parseRouteQuery(query);
                options.applyParsedFilters(parsed);
                void options.fetchPage(parsed.page);
            },
            { immediate: options.immediate ?? true },
        );
    }

    return {
        hasRouteSync,
        load,
    };
};
