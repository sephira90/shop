import type { LocationQueryRaw } from "vue-router";

import { normalizePageFromQuery } from "@/queries/route-query";

type ObjectKey<TObject extends object> = Extract<keyof TObject, string>;

export interface RouteFieldSchema<
    TFilters extends object,
    TKey extends ObjectKey<TFilters> = ObjectKey<TFilters>,
> {
    key: TKey;
    queryKey: string;
    parse: (value: unknown) => TFilters[TKey];
    format: (value: TFilters[TKey]) => string | null;
}

export interface RouteQuerySchema<TFilters extends object> {
    fields: readonly {
        [TKey in ObjectKey<TFilters>]: RouteFieldSchema<TFilters, TKey>;
    }[ObjectKey<TFilters>][];
}

export type RouteFilters<TFilters extends object> = TFilters & {
    page: number;
};

const schemaKeysEqual = <TFilters extends object>(
    left: RouteFilters<TFilters>,
    right: RouteFilters<TFilters>,
    fields: readonly RouteFieldSchema<TFilters>[],
): boolean => {
    if (left.page !== right.page) {
        return false;
    }

    return fields.every((field) => left[field.key] === right[field.key]);
};

export const parseRouteFilters = <TFilters extends object>(
    query: Readonly<Record<string, unknown>>,
    schema: RouteQuerySchema<TFilters>,
    defaults: TFilters,
): RouteFilters<TFilters> => {
    const parsed = { ...defaults } as TFilters;

    schema.fields.forEach((field) => {
        (parsed as Record<ObjectKey<TFilters>, TFilters[ObjectKey<TFilters>]>)[field.key] =
            field.parse(query[field.queryKey]);
    });

    return {
        ...parsed,
        page: normalizePageFromQuery(query.page),
    };
};

export const buildRouteQuery = <TFilters extends object>(
    filters: RouteFilters<TFilters>,
    schema: RouteQuerySchema<TFilters>,
): LocationQueryRaw => {
    const query: LocationQueryRaw = {};

    schema.fields.forEach((field) => {
        const formatted = field.format(filters[field.key]);

        if (formatted !== null && formatted !== "") {
            query[field.queryKey] = formatted;
        }
    });

    if (filters.page > 1) {
        query.page = String(filters.page);
    }

    return query;
};

export const isSameRouteQuery = <TFilters extends object>(
    left: Readonly<Record<string, unknown>>,
    right: Readonly<Record<string, unknown>>,
    schema: RouteQuerySchema<TFilters>,
    defaults: TFilters,
): boolean => {
    const parsedLeft = parseRouteFilters(left, schema, defaults);
    const parsedRight = parseRouteFilters(right, schema, defaults);

    return schemaKeysEqual(parsedLeft, parsedRight, schema.fields);
};
