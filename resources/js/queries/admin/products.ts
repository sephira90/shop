import type { LocationQueryRaw } from "vue-router";

import type { AdminProductListParams } from "@/types/admin-products";
import { normalizePageFromQuery, toSingleQueryValue } from "@/queries/route-query";

export interface AdminProductFilters {
    searchQuery: string;
}

export interface AdminProductRouteFilters extends AdminProductFilters {
    page: number;
}

export const parseAdminProductFiltersFromRouteQuery = (
    query: Readonly<Record<string, unknown>>,
): AdminProductRouteFilters => {
    return {
        searchQuery: toSingleQueryValue(query.q).trim(),
        page: normalizePageFromQuery(query.page),
    };
};

export const buildAdminProductRouteQuery = (
    filters: AdminProductRouteFilters,
): LocationQueryRaw => {
    const routeQuery: LocationQueryRaw = {};
    const query = filters.searchQuery.trim();

    if (query !== "") {
        routeQuery.q = query;
    }

    if (filters.page > 1) {
        routeQuery.page = String(filters.page);
    }

    return routeQuery;
};

export const isSameAdminProductRouteQuery = (
    left: Readonly<Record<string, unknown>>,
    right: Readonly<Record<string, unknown>>,
): boolean => {
    const parsedLeft = parseAdminProductFiltersFromRouteQuery(left);
    const parsedRight = parseAdminProductFiltersFromRouteQuery(right);

    return (
        parsedLeft.searchQuery === parsedRight.searchQuery && parsedLeft.page === parsedRight.page
    );
};

export const buildAdminProductListParams = (
    page: number,
    filters: AdminProductFilters,
): AdminProductListParams => {
    const params: AdminProductListParams = { page };
    const query = filters.searchQuery.trim();

    if (query !== "") {
        params.q = query;
    }

    return params;
};
