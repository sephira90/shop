import type { LocationQueryRaw } from "vue-router";

import type { AdminCategoryListParams, CategoryStatusFilter } from "@/types/admin-categories";
import { normalizeEnumQuery, toSingleQueryValue } from "@/queries/route-query";

import {
    buildAdminRouteQuery,
    isSameAdminRouteQuery,
    parseAdminRouteFilters,
    type AdminRouteQuerySchema,
} from "./route-query-schema";

export interface AdminCategoryFilters {
    searchQuery: string;
    statusFilter: CategoryStatusFilter;
}

export interface AdminCategoryRouteFilters extends AdminCategoryFilters {
    page: number;
}

const ALLOWED_STATUS_FILTERS: CategoryStatusFilter[] = ["all", "active", "inactive"];
const DEFAULT_ROUTE_FILTERS: AdminCategoryFilters = {
    searchQuery: "",
    statusFilter: "all",
};
const CATEGORY_ROUTE_QUERY_SCHEMA: AdminRouteQuerySchema<AdminCategoryFilters> = {
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
            parse: (value) => normalizeEnumQuery(value, ALLOWED_STATUS_FILTERS, "all"),
            format: (value) => (value === "all" ? null : String(value)),
        },
    ],
};

export const parseAdminCategoryFiltersFromRouteQuery = (
    query: Readonly<Record<string, unknown>>,
): AdminCategoryRouteFilters => {
    return parseAdminRouteFilters(query, CATEGORY_ROUTE_QUERY_SCHEMA, DEFAULT_ROUTE_FILTERS);
};

export const buildAdminCategoryRouteQuery = (
    filters: AdminCategoryRouteFilters,
): LocationQueryRaw => {
    return buildAdminRouteQuery(filters, CATEGORY_ROUTE_QUERY_SCHEMA);
};

export const isSameAdminCategoryRouteQuery = (
    left: Readonly<Record<string, unknown>>,
    right: Readonly<Record<string, unknown>>,
): boolean => {
    return isSameAdminRouteQuery(left, right, CATEGORY_ROUTE_QUERY_SCHEMA, DEFAULT_ROUTE_FILTERS);
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
