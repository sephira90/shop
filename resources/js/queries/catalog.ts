import type { LocationQueryRaw } from 'vue-router';

import type { CatalogProductListParams, CatalogSort } from '@/types/catalog';

const DEFAULT_SORT: CatalogSort = 'newest';
const CATALOG_SORTS: CatalogSort[] = ['newest', 'price_asc', 'price_desc', 'name_asc'];

export interface CatalogFilters {
    q: string;
    sort: CatalogSort;
}

const toSingleQueryValue = (value: unknown): string => {
    if (Array.isArray(value)) {
        const first = value.find((item) => typeof item === 'string');

        return typeof first === 'string' ? first : '';
    }

    return typeof value === 'string' ? value : '';
};

const normalizeSort = (value: unknown): CatalogSort => {
    const normalized = toSingleQueryValue(value).trim().toLowerCase();

    return CATALOG_SORTS.includes(normalized as CatalogSort) ? (normalized as CatalogSort) : DEFAULT_SORT;
};

export const parseCatalogFiltersFromRouteQuery = (query: Record<string, unknown>): CatalogFilters => {
    return {
        q: toSingleQueryValue(query.q).trim(),
        sort: normalizeSort(query.sort),
    };
};

export const buildCatalogRouteQuery = (filters: CatalogFilters): LocationQueryRaw => {
    const query = filters.q.trim();
    const routeQuery: LocationQueryRaw = {};

    if (query !== '') {
        routeQuery.q = query;
    }

    if (filters.sort !== DEFAULT_SORT) {
        routeQuery.sort = filters.sort;
    }

    return routeQuery;
};

export const buildCatalogListParams = (filters: CatalogFilters): CatalogProductListParams => {
    const query = filters.q.trim();
    const params: CatalogProductListParams = {};

    if (query !== '') {
        params.q = query;
    }

    if (filters.sort !== DEFAULT_SORT) {
        params.sort = filters.sort;
    }

    return params;
};

export const isSameCatalogRouteQuery = (left: Record<string, unknown>, right: Record<string, unknown>): boolean => {
    const parsedLeft = parseCatalogFiltersFromRouteQuery(left);
    const parsedRight = parseCatalogFiltersFromRouteQuery(right);

    return parsedLeft.q === parsedRight.q && parsedLeft.sort === parsedRight.sort;
};
