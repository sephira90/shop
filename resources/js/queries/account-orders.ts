import type { LocationQueryRaw } from "vue-router";

import type {
    AccountOrderStatusFilter,
    AccountOrderSummaryListParams,
} from "@/types/account-orders";
import { normalizeEnumQuery, toSingleQueryValue } from "@/queries/route-query";
import {
    buildRouteQuery,
    isSameRouteQuery,
    parseRouteFilters,
    type RouteQuerySchema,
} from "@/queries/route-query-schema";

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

interface AccountOrdersRouteFilters {
    searchQuery: string;
    statusFilter: AccountOrderStatusFilter;
}

export interface AccountOrdersFilters extends AccountOrdersRouteFilters {
    page: number;
}

const DEFAULT_ROUTE_FILTERS: AccountOrdersRouteFilters = {
    searchQuery: "",
    statusFilter: "all",
};

const normalizeStatusFilter = (value: unknown): AccountOrderStatusFilter => {
    return normalizeEnumQuery(value, ALLOWED_STATUS_FILTERS, "all");
};

const ACCOUNT_ORDERS_ROUTE_QUERY_SCHEMA: RouteQuerySchema<AccountOrdersRouteFilters> = {
    fields: [
        {
            key: "searchQuery",
            queryKey: "q",
            parse: (value) => toSingleQueryValue(value).trim(),
            format: (value) => {
                const query = String(value).trim();

                return query === "" ? null : query;
            },
        },
        {
            key: "statusFilter",
            queryKey: "status",
            parse: (value) => normalizeStatusFilter(value),
            format: (value) => (value === "all" ? null : value),
        },
    ],
};

export const parseAccountOrdersFiltersFromRouteQuery = (
    query: Readonly<Record<string, unknown>>,
): AccountOrdersFilters => {
    return parseRouteFilters(query, ACCOUNT_ORDERS_ROUTE_QUERY_SCHEMA, DEFAULT_ROUTE_FILTERS);
};

export const buildAccountOrdersRouteQuery = (filters: AccountOrdersFilters): LocationQueryRaw => {
    return buildRouteQuery(filters, ACCOUNT_ORDERS_ROUTE_QUERY_SCHEMA);
};

export const isSameAccountOrdersRouteQuery = (
    left: Readonly<Record<string, unknown>>,
    right: Readonly<Record<string, unknown>>,
): boolean => {
    return isSameRouteQuery(left, right, ACCOUNT_ORDERS_ROUTE_QUERY_SCHEMA, DEFAULT_ROUTE_FILTERS);
};

export const buildAccountOrdersListParams = (
    page: number,
    filters: Pick<AccountOrdersFilters, "searchQuery" | "statusFilter">,
): AccountOrderSummaryListParams => {
    const params: AccountOrderSummaryListParams = {
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
