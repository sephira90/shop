import type { LocationQueryRaw } from "vue-router";

import type { AccountOrderListParams, AccountOrderStatusFilter } from "@/types/account-orders";
import {
    normalizeEnumQuery,
    normalizePageFromQuery,
    toSingleQueryValue,
} from "@/queries/route-query";

const ALLOWED_STATUS_FILTERS: AccountOrderStatusFilter[] = [
    "all",
    "pending",
    "paid",
    "processing",
    "shipped",
    "completed",
    "cancelled",
    "refunded",
];

export interface AccountOrdersFilters {
    searchQuery: string;
    statusFilter: AccountOrderStatusFilter;
    page: number;
}

const normalizeStatusFilter = (value: unknown): AccountOrderStatusFilter => {
    return normalizeEnumQuery(value, ALLOWED_STATUS_FILTERS, "all");
};

export const parseAccountOrdersFiltersFromRouteQuery = (
    query: Readonly<Record<string, unknown>>,
): AccountOrdersFilters => {
    return {
        searchQuery: toSingleQueryValue(query.q).trim(),
        statusFilter: normalizeStatusFilter(query.status),
        page: normalizePageFromQuery(query.page),
    };
};

export const buildAccountOrdersRouteQuery = (filters: AccountOrdersFilters): LocationQueryRaw => {
    const query = filters.searchQuery.trim();
    const routeQuery: LocationQueryRaw = {};

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

export const isSameAccountOrdersRouteQuery = (
    left: Readonly<Record<string, unknown>>,
    right: Readonly<Record<string, unknown>>,
): boolean => {
    const parsedLeft = parseAccountOrdersFiltersFromRouteQuery(left);
    const parsedRight = parseAccountOrdersFiltersFromRouteQuery(right);

    return (
        parsedLeft.searchQuery === parsedRight.searchQuery &&
        parsedLeft.statusFilter === parsedRight.statusFilter &&
        parsedLeft.page === parsedRight.page
    );
};

export const buildAccountOrdersListParams = (
    page: number,
    filters: Pick<AccountOrdersFilters, "searchQuery" | "statusFilter">,
): AccountOrderListParams => {
    const params: AccountOrderListParams = {
        page,
    };

    const query = filters.searchQuery.trim();
    if (query !== "") {
        params.q = query;
    }

    if (filters.statusFilter !== "all") {
        params.status = filters.statusFilter;
    }

    return params;
};
