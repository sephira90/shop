import type { LocationQueryRaw } from "vue-router";

import type { PromotionListParams, PromotionStatusFilter } from "@/types/admin-promotions";
import { normalizeEnumQuery, toSingleQueryValue } from "@/queries/route-query";

import {
    buildAdminRouteQuery,
    isSameAdminRouteQuery,
    parseAdminRouteFilters,
    type AdminRouteQuerySchema,
} from "./route-query-schema";

export interface AdminPromotionFilters {
    searchQuery: string;
    statusFilter: PromotionStatusFilter;
}

export interface AdminPromotionRouteFilters extends AdminPromotionFilters {
    page: number;
}

const ALLOWED_STATUS_FILTERS: PromotionStatusFilter[] = ["all", "active", "inactive"];
const DEFAULT_ROUTE_FILTERS: AdminPromotionFilters = {
    searchQuery: "",
    statusFilter: "all",
};
const PROMOTION_ROUTE_QUERY_SCHEMA: AdminRouteQuerySchema<AdminPromotionFilters> = {
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

export const parseAdminPromotionFiltersFromRouteQuery = (
    query: Readonly<Record<string, unknown>>,
): AdminPromotionRouteFilters => {
    return parseAdminRouteFilters(query, PROMOTION_ROUTE_QUERY_SCHEMA, DEFAULT_ROUTE_FILTERS);
};

export const buildAdminPromotionRouteQuery = (
    filters: AdminPromotionRouteFilters,
): LocationQueryRaw => {
    return buildAdminRouteQuery(filters, PROMOTION_ROUTE_QUERY_SCHEMA);
};

export const isSameAdminPromotionRouteQuery = (
    left: Readonly<Record<string, unknown>>,
    right: Readonly<Record<string, unknown>>,
): boolean => {
    return isSameAdminRouteQuery(left, right, PROMOTION_ROUTE_QUERY_SCHEMA, DEFAULT_ROUTE_FILTERS);
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
