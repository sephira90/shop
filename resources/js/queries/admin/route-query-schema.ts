import type { LocationQueryRaw } from "vue-router";

import { normalizePageFromQuery } from "@/queries/route-query";

type ObjectKey<TObject extends object> = Extract<keyof TObject, string>;

export interface AdminRouteFieldSchema<
    TFilters extends object,
    TKey extends ObjectKey<TFilters> = ObjectKey<TFilters>,
> {
    key: TKey;
    queryKey: string;
    parse: (value: unknown) => TFilters[TKey];
    format: (value: TFilters[TKey]) => string | null;
}

export interface AdminRouteQuerySchema<TFilters extends object> {
    fields: readonly {
        [TKey in ObjectKey<TFilters>]: AdminRouteFieldSchema<TFilters, TKey>;
    }[ObjectKey<TFilters>][];
}

export type AdminRouteFilters<TFilters extends object> = TFilters & {
    page: number;
};

const schemaKeysEqual = <TFilters extends object>(
    left: AdminRouteFilters<TFilters>,
    right: AdminRouteFilters<TFilters>,
    fields: readonly AdminRouteFieldSchema<TFilters>[],
): boolean => {
    if (left.page !== right.page) {
        return false;
    }

    return fields.every((field) => left[field.key] === right[field.key]);
};

export const parseAdminRouteFilters = <TFilters extends object>(
    query: Readonly<Record<string, unknown>>,
    schema: AdminRouteQuerySchema<TFilters>,
    defaults: TFilters,
): AdminRouteFilters<TFilters> => {
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

export const buildAdminRouteQuery = <TFilters extends object>(
    filters: AdminRouteFilters<TFilters>,
    schema: AdminRouteQuerySchema<TFilters>,
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

export const isSameAdminRouteQuery = <TFilters extends object>(
    left: Readonly<Record<string, unknown>>,
    right: Readonly<Record<string, unknown>>,
    schema: AdminRouteQuerySchema<TFilters>,
    defaults: TFilters,
): boolean => {
    const parsedLeft = parseAdminRouteFilters(left, schema, defaults);
    const parsedRight = parseAdminRouteFilters(right, schema, defaults);

    return schemaKeysEqual(parsedLeft, parsedRight, schema.fields);
};
