import { describe, expect, it } from 'vitest';

import { buildAdminProductListParams } from '@/queries/admin/products';

describe('product query', () => {
    it('builds list params from search state', () => {
        expect(
            buildAdminProductListParams(4, {
                searchQuery: '  SKU-001  ',
            }),
        ).toEqual({
            page: 4,
            q: 'SKU-001',
        });
    });

    it('omits query param when search is empty', () => {
        expect(
            buildAdminProductListParams(1, {
                searchQuery: '   ',
            }),
        ).toEqual({
            page: 1,
        });
    });
});
