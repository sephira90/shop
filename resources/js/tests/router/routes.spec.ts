import { describe, expect, it } from 'vitest';

import { appRoutes } from '@/router';

describe('router routes', () => {
    it('uses lazy components for all non-redirect routes', () => {
        const routesWithComponents = appRoutes.filter((route) => route.component !== undefined);

        expect(routesWithComponents.length).toBeGreaterThan(0);

        routesWithComponents.forEach((route) => {
            expect(typeof route.component).toBe('function');
        });
    });

    it('keeps auth meta for protected areas', () => {
        const accountProfileRoute = appRoutes.find((route) => route.path === '/account/profile');
        const adminDashboardRoute = appRoutes.find((route) => route.path === '/admin');

        expect(accountProfileRoute?.meta).toEqual({
            requiresAuth: true,
            roles: ['customer', 'manager', 'admin'],
        });
        expect(adminDashboardRoute?.meta).toEqual({
            requiresAuth: true,
            roles: ['manager', 'admin'],
        });
    });
});
