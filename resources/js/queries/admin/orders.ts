import type { LocationQueryRaw } from "vue-router";

import type { AdminOrderListParams } from "@/types/admin-orders";
import {
    normalizeEnumQuery,
    normalizePageFromQuery,
    toSingleQueryValue,
} from "@/queries/route-query";

export interface AdminOrderFilters {
    search: string;
    orderStatus: string;
    paymentStatus: string;
    shipmentStatus: string;
}

export interface AdminOrderRouteFilters extends AdminOrderFilters {
    page: number;
}

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

export const parseAdminOrderFiltersFromRouteQuery = (
    query: Readonly<Record<string, unknown>>,
): AdminOrderRouteFilters => {
    return {
        search: toSingleQueryValue(query.q).trim(),
        orderStatus: normalizeEnumQuery(query.status, ALLOWED_ORDER_STATUSES, "all"),
        paymentStatus: normalizeEnumQuery(query.payment_status, ALLOWED_PAYMENT_STATUSES, "all"),
        shipmentStatus: normalizeEnumQuery(query.shipment_status, ALLOWED_SHIPMENT_STATUSES, "all"),
        page: normalizePageFromQuery(query.page),
    };
};

export const buildAdminOrderRouteQuery = (filters: AdminOrderRouteFilters): LocationQueryRaw => {
    const routeQuery: LocationQueryRaw = {};
    const query = filters.search.trim();

    if (query !== "") {
        routeQuery.q = query;
    }

    if (filters.orderStatus !== "all") {
        routeQuery.status = filters.orderStatus;
    }

    if (filters.paymentStatus !== "all") {
        routeQuery.payment_status = filters.paymentStatus;
    }

    if (filters.shipmentStatus !== "all") {
        routeQuery.shipment_status = filters.shipmentStatus;
    }

    if (filters.page > 1) {
        routeQuery.page = String(filters.page);
    }

    return routeQuery;
};

export const isSameAdminOrderRouteQuery = (
    left: Readonly<Record<string, unknown>>,
    right: Readonly<Record<string, unknown>>,
): boolean => {
    const parsedLeft = parseAdminOrderFiltersFromRouteQuery(left);
    const parsedRight = parseAdminOrderFiltersFromRouteQuery(right);

    return (
        parsedLeft.search === parsedRight.search &&
        parsedLeft.orderStatus === parsedRight.orderStatus &&
        parsedLeft.paymentStatus === parsedRight.paymentStatus &&
        parsedLeft.shipmentStatus === parsedRight.shipmentStatus &&
        parsedLeft.page === parsedRight.page
    );
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
