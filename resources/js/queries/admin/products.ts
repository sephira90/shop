import type { LocationQueryRaw } from "vue-router";

import type { AdminProductListParams } from "@/types/admin-products";
import { toSingleQueryValue } from "@/queries/route-query";

import {
    buildAdminRouteQuery,
    isSameAdminRouteQuery,
    parseAdminRouteFilters,
    type AdminRouteQuerySchema,
} from "./route-query-schema";

export interface AdminProductFilters {
    searchQuery: string;
}

export interface AdminProductRouteFilters extends AdminProductFilters {
    page: number;
}

const DEFAULT_ROUTE_FILTERS: AdminProductFilters = {
    searchQuery: "",
};
const PRODUCT_ROUTE_QUERY_SCHEMA: AdminRouteQuerySchema<AdminProductFilters> = {
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
    ],
};

export const parseAdminProductFiltersFromRouteQuery = (
    query: Readonly<Record<string, unknown>>,
): AdminProductRouteFilters => {
    return parseAdminRouteFilters(query, PRODUCT_ROUTE_QUERY_SCHEMA, DEFAULT_ROUTE_FILTERS);
};

export const buildAdminProductRouteQuery = (
    filters: AdminProductRouteFilters,
): LocationQueryRaw => {
    return buildAdminRouteQuery(filters, PRODUCT_ROUTE_QUERY_SCHEMA);
};

export const isSameAdminProductRouteQuery = (
    left: Readonly<Record<string, unknown>>,
    right: Readonly<Record<string, unknown>>,
): boolean => {
    return isSameAdminRouteQuery(left, right, PRODUCT_ROUTE_QUERY_SCHEMA, DEFAULT_ROUTE_FILTERS);
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
