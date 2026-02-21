import { describe, expect, it } from 'vitest';

import {
    buildCatalogListParams,
    buildCatalogRouteQuery,
    isSameCatalogRouteQuery,
    parseCatalogFiltersFromRouteQuery,
} from '@/queries/catalog';

describe('catalog query', () => {
    it('parses route query with normalized values', () => {
        expect(
            parseCatalogFiltersFromRouteQuery({
                q: '  boots  ',
                sort: 'price_desc',
            }),
        ).toEqual({
            q: 'boots',
            sort: 'price_desc',
        });
    });

    it('falls back to default sort for invalid values', () => {
        expect(
            parseCatalogFiltersFromRouteQuery({
                q: '',
                sort: 'random',
            }),
        ).toEqual({
            q: '',
            sort: 'newest',
        });
    });

    it('builds route query and params without defaults', () => {
        const filters = {
            q: '  phone  ',
            sort: 'newest' as const,
        };

        expect(buildCatalogRouteQuery(filters)).toEqual({
            q: 'phone',
        });
        expect(buildCatalogListParams(filters)).toEqual({
            q: 'phone',
        });
    });

    it('compares route queries by normalized filters', () => {
        expect(
            isSameCatalogRouteQuery(
                { q: '  phone  ', sort: 'price_asc' },
                { q: 'phone', sort: 'price_asc' },
            ),
        ).toBe(true);
        expect(
            isSameCatalogRouteQuery(
                { q: 'phone', sort: 'price_asc' },
                { q: 'phone', sort: 'price_desc' },
            ),
        ).toBe(false);
    });
});
