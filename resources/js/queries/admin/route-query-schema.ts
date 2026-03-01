import {
    buildRouteQuery,
    isSameRouteQuery,
    parseRouteFilters,
    type RouteFieldSchema,
    type RouteFilters,
    type RouteQuerySchema,
} from "@/queries/route-query-schema";

export type AdminRouteFieldSchema<
    TFilters extends object,
    TKey extends Extract<keyof TFilters, string> = Extract<keyof TFilters, string>,
> = RouteFieldSchema<TFilters, TKey>;

export type AdminRouteQuerySchema<TFilters extends object> = RouteQuerySchema<TFilters>;

export type AdminRouteFilters<TFilters extends object> = RouteFilters<TFilters>;

export const parseAdminRouteFilters = parseRouteFilters;

export const buildAdminRouteQuery = buildRouteQuery;

export const isSameAdminRouteQuery = isSameRouteQuery;
