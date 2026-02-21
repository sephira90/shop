import { describe, expect, it } from 'vitest';

import { buildAdminCategoryListParams } from '@/queries/admin/categories';

describe('category query', () => {
    it('builds params with search and active status', () => {
        expect(
            buildAdminCategoryListParams(2, {
                searchQuery: '  shoes  ',
                statusFilter: 'active',
            }),
        ).toEqual({
            page: 2,
            per_page: 200,
            q: 'shoes',
            is_active: true,
        });
    });

    it('builds params with inactive status', () => {
        expect(
            buildAdminCategoryListParams(3, {
                searchQuery: '',
                statusFilter: 'inactive',
            }),
        ).toEqual({
            page: 3,
            per_page: 200,
            is_active: false,
        });
    });

    it('omits optional filters when reset', () => {
        expect(
            buildAdminCategoryListParams(1, {
                searchQuery: '   ',
                statusFilter: 'all',
            }),
        ).toEqual({
            page: 1,
            per_page: 200,
        });
    });
});
