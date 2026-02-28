import type { LocationQueryRaw } from "vue-router";

import type { AdminOrderListParams } from "@/types/admin-orders";
import { normalizeEnumQuery, toSingleQueryValue } from "@/queries/route-query";

import {
    buildAdminRouteQuery,
    isSameAdminRouteQuery,
    parseAdminRouteFilters,
    type AdminRouteQuerySchema,
} from "./route-query-schema";

export interface AdminOrderFilters {
    search: string;
    orderStatus: string;
    paymentStatus: string;
    shipmentStatus: string;
}

export interface AdminOrderRouteFilters extends AdminOrderFilters {
    page: number;
}

const DEFAULT_ROUTE_FILTERS: AdminOrderFilters = {
    search: "",
    orderStatus: "all",
    paymentStatus: "all",
    shipmentStatus: "all",
};

const ALLOWED_ORDER_STATUSES = [
    "all",
    "pending",
    "paid",
    "processing",
    "shipped",
    "completed",
    "cancelled",
    "refunded",
] as const;

const ALLOWED_PAYMENT_STATUSES = [
    "all",
    "pending",
    "authorized",
    "captured",
    "failed",
    "refunded",
] as const;

const ALLOWED_SHIPMENT_STATUSES = [
    "all",
    "pending",
    "packed",
    "shipped",
    "delivered",
    "returned",
] as const;
const ORDER_ROUTE_QUERY_SCHEMA: AdminRouteQuerySchema<AdminOrderFilters> = {
    fields: [
        {
            key: "search",
            queryKey: "q",
            parse: (value) => toSingleQueryValue(value).trim(),
            format: (value) => {
                const query = String(value).trim();

                return query === "" ? null : query;
            },
        },
        {
            key: "orderStatus",
            queryKey: "status",
            parse: (value) => normalizeEnumQuery(value, ALLOWED_ORDER_STATUSES, "all"),
            format: (value) => (value === "all" ? null : String(value)),
        },
        {
            key: "paymentStatus",
            queryKey: "payment_status",
            parse: (value) => normalizeEnumQuery(value, ALLOWED_PAYMENT_STATUSES, "all"),
            format: (value) => (value === "all" ? null : String(value)),
        },
        {
            key: "shipmentStatus",
            queryKey: "shipment_status",
            parse: (value) => normalizeEnumQuery(value, ALLOWED_SHIPMENT_STATUSES, "all"),
            format: (value) => (value === "all" ? null : String(value)),
        },
    ],
};

export const parseAdminOrderFiltersFromRouteQuery = (
    query: Readonly<Record<string, unknown>>,
): AdminOrderRouteFilters => {
    return parseAdminRouteFilters(query, ORDER_ROUTE_QUERY_SCHEMA, DEFAULT_ROUTE_FILTERS);
};

export const buildAdminOrderRouteQuery = (filters: AdminOrderRouteFilters): LocationQueryRaw => {
    return buildAdminRouteQuery(filters, ORDER_ROUTE_QUERY_SCHEMA);
};

export const isSameAdminOrderRouteQuery = (
    left: Readonly<Record<string, unknown>>,
    right: Readonly<Record<string, unknown>>,
): boolean => {
    return isSameAdminRouteQuery(left, right, ORDER_ROUTE_QUERY_SCHEMA, DEFAULT_ROUTE_FILTERS);
};

export const buildAdminOrderListParams = (
    page: number,
    filters: AdminOrderFilters,
): AdminOrderListParams => {
    const params: AdminOrderListParams = { page };
    const query = filters.search.trim();

    if (query !== "") {
        params.q = query;
    }

    if (filters.orderStatus !== "all") {
        params.status = filters.orderStatus;
    }

    if (filters.paymentStatus !== "all") {
        params.payment_status = filters.paymentStatus;
    }

    if (filters.shipmentStatus !== "all") {
        params.shipment_status = filters.shipmentStatus;
    }

    return params;
};
