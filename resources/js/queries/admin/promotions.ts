import type { LocationQueryRaw } from "vue-router";

import type { PromotionListParams, PromotionStatusFilter } from "@/types/admin-promotions";
import {
    normalizeEnumQuery,
    normalizePageFromQuery,
    toSingleQueryValue,
} from "@/queries/route-query";

export interface AdminPromotionFilters {
    searchQuery: string;
    statusFilter: PromotionStatusFilter;
}

export interface AdminPromotionRouteFilters extends AdminPromotionFilters {
    page: number;
}

const ALLOWED_STATUS_FILTERS: PromotionStatusFilter[] = ["all", "active", "inactive"];

export const parseAdminPromotionFiltersFromRouteQuery = (
    query: Readonly<Record<string, unknown>>,
): AdminPromotionRouteFilters => {
    return {
        searchQuery: toSingleQueryValue(query.q).trim(),
        statusFilter: normalizeEnumQuery(query.status, ALLOWED_STATUS_FILTERS, "all"),
        page: normalizePageFromQuery(query.page),
    };
};

export const buildAdminPromotionRouteQuery = (
    filters: AdminPromotionRouteFilters,
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

export const isSameAdminPromotionRouteQuery = (
    left: Readonly<Record<string, unknown>>,
    right: Readonly<Record<string, unknown>>,
): boolean => {
    const parsedLeft = parseAdminPromotionFiltersFromRouteQuery(left);
    const parsedRight = parseAdminPromotionFiltersFromRouteQuery(right);

    return (
        parsedLeft.searchQuery === parsedRight.searchQuery &&
        parsedLeft.statusFilter === parsedRight.statusFilter &&
        parsedLeft.page === parsedRight.page
    );
};

export const buildAdminPromotionListParams = (
    page: number,
    filters: AdminPromotionFilters,
): PromotionListParams => {
    const params: PromotionListParams = { page };
    const query = filters.searchQuery.trim();

    if (query !== "") {
        params.q = query;
    }

    if (filters.statusFilter !== "all") {
        params.is_active = filters.statusFilter === "active";
    }

    return params;
};
