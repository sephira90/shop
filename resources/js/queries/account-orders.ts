import type { LocationQueryRaw } from "vue-router";

import type { AccountOrderListParams, AccountOrderStatusFilter } from "@/types/account-orders";

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

const toSingleQueryValue = (value: unknown): string => {
    if (Array.isArray(value)) {
        const first = value.find((item) => typeof item === "string");

        return typeof first === "string" ? first : "";
    }

    return typeof value === "string" ? value : "";
};

const normalizeStatusFilter = (value: unknown): AccountOrderStatusFilter => {
    const normalized = toSingleQueryValue(value).trim().toLowerCase();

    return ALLOWED_STATUS_FILTERS.includes(normalized as AccountOrderStatusFilter)
        ? (normalized as AccountOrderStatusFilter)
        : "all";
};

const normalizePage = (value: unknown): number => {
    const raw = toSingleQueryValue(value).trim();
    const parsed = Number(raw);

    if (!Number.isInteger(parsed) || parsed < 1) {
        return 1;
    }

    return parsed;
};

export const parseAccountOrdersFiltersFromRouteQuery = (
    query: Record<string, unknown>,
): AccountOrdersFilters => {
    return {
        searchQuery: toSingleQueryValue(query.q).trim(),
        statusFilter: normalizeStatusFilter(query.status),
        page: normalizePage(query.page),
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
    left: Record<string, unknown>,
    right: Record<string, unknown>,
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
