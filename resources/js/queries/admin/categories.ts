import type { LocationQueryRaw } from "vue-router";

import type { AdminCategoryListParams, CategoryStatusFilter } from "@/types/admin-categories";
import {
    normalizeEnumQuery,
    normalizePageFromQuery,
    toSingleQueryValue,
} from "@/queries/route-query";

export interface AdminCategoryFilters {
    searchQuery: string;
    statusFilter: CategoryStatusFilter;
}

export interface AdminCategoryRouteFilters extends AdminCategoryFilters {
    page: number;
}

const ALLOWED_STATUS_FILTERS: CategoryStatusFilter[] = ["all", "active", "inactive"];

export const parseAdminCategoryFiltersFromRouteQuery = (
    query: Readonly<Record<string, unknown>>,
): AdminCategoryRouteFilters => {
    return {
        searchQuery: toSingleQueryValue(query.q).trim(),
        statusFilter: normalizeEnumQuery(query.status, ALLOWED_STATUS_FILTERS, "all"),
        page: normalizePageFromQuery(query.page),
    };
};

export const buildAdminCategoryRouteQuery = (
    filters: AdminCategoryRouteFilters,
): LocationQueryRaw => {
    const routeQuery: LocationQueryRaw = {};
    const query = filters.searchQuery.trim();

    if (query !== "") {
        routeQuery.q = query;
    }

    if (filters.statusFilter !== "all") {
        routeQuery.status = filters.statusFilter;
    }

    if (filters.page > 1) {
        routeQuery.page = String(filters.page);
    }

    return routeQuery;
};

export const isSameAdminCategoryRouteQuery = (
    left: Readonly<Record<string, unknown>>,
    right: Readonly<Record<string, unknown>>,
): boolean => {
    const parsedLeft = parseAdminCategoryFiltersFromRouteQuery(left);
    const parsedRight = parseAdminCategoryFiltersFromRouteQuery(right);

    return (
        parsedLeft.searchQuery === parsedRight.searchQuery &&
        parsedLeft.statusFilter === parsedRight.statusFilter &&
        parsedLeft.page === parsedRight.page
    );
};

export const buildAdminCategoryListParams = (
    page: number,
    filters: AdminCategoryFilters,
): AdminCategoryListParams => {
    const params: AdminCategoryListParams = {
        page,
        per_page: 200,
    };
    const query = filters.searchQuery.trim();

    if (query !== "") {
        params.q = query;
    }

    if (filters.statusFilter !== "all") {
        params.is_active = filters.statusFilter === "active";
    }

    return params;
};
