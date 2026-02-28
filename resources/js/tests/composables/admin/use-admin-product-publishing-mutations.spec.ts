import { describe, expect, it, vi } from "vitest";
import { effectScope, ref } from "vue";

import { useAdminProductPublishingMutations } from "@/composables/admin/products/useAdminProductPublishingMutations";
import type { ExecuteAdminMutation } from "@/composables/useAdminMutation";
import type { AdminProduct } from "@/types/admin-products";

vi.mock("@/api/admin/products", () => ({
    refreshAdminCatalogCache: vi.fn(),
    updateAdminProduct: vi.fn(),
}));

import { refreshAdminCatalogCache, updateAdminProduct } from "@/api/admin/products";

const refreshAdminCatalogCacheMock = refreshAdminCatalogCache as unknown as ReturnType<
    typeof vi.fn
>;
const updateAdminProductMock = updateAdminProduct as unknown as ReturnType<typeof vi.fn>;

const buildProduct = (id: number, overrides: Partial<AdminProduct> = {}): AdminProduct => ({
    id,
    sku: `SKU-${id}`,
    name: `Product ${id}`,
    slug: `product-${id}`,
    short_description: null,
    description: null,
    status: "draft",
    is_featured: false,
    brand: null,
    weight_grams: null,
    category: null,
    meta: {
        title: null,
        description: null,
    },
    variants: [],
    published_at: null,
    ...overrides,
});

const createExecuteMutation = (): ExecuteAdminMutation => {
    return async (options) => {
        options.setPending?.(true);

        try {
            const result = await options.run();

            if (options.onSuccess) {
                await options.onSuccess(result);
            }

            return result;
        } catch (error: unknown) {
            if (options.onError) {
                await options.onError(error);
            }

            return null;
        } finally {
            options.setPending?.(false);
        }
    };
};

describe("useAdminProductPublishingMutations", () => {
    it("publishes hidden product to catalog and reloads current page", async () => {
        const showSuccess = vi.fn();
        const loadProducts = vi.fn(async () => {});
        updateAdminProductMock.mockResolvedValue(undefined);

        const scope = effectScope();
        const api = scope.run(() =>
            useAdminProductPublishingMutations({
                query: {
                    page: ref(2),
                    loadProducts,
                },
                executeMutation: createExecuteMutation(),
                notice: {
                    showSuccess,
                },
            }),
        );

        expect(api).not.toBeNull();
        if (!api) {
            scope.stop();
            return;
        }

        const product = buildProduct(5);

        await api.toggleCatalogVisibility(product);

        expect(updateAdminProductMock).toHaveBeenCalledWith(
            5,
            expect.objectContaining({
                status: "active",
                published_at: expect.any(String),
            }),
        );
        expect(loadProducts).toHaveBeenCalledWith(2);
        expect(showSuccess).toHaveBeenCalledWith(
            "Product published to catalog. Public cache may refresh within 60 seconds.",
        );
        expect(api.isVisibilityUpdatingId.value).toBeNull();

        scope.stop();
    });

    it("refreshes catalog cache and reports versioned success message", async () => {
        const showSuccess = vi.fn();
        refreshAdminCatalogCacheMock.mockResolvedValue(42);

        const scope = effectScope();
        const api = scope.run(() =>
            useAdminProductPublishingMutations({
                query: {
                    page: ref(1),
                    loadProducts: vi.fn(async () => {}),
                },
                executeMutation: createExecuteMutation(),
                notice: {
                    showSuccess,
                },
            }),
        );

        expect(api).not.toBeNull();
        if (!api) {
            scope.stop();
            return;
        }

        expect(api.isVisibleInCatalog(buildProduct(7))).toBe(false);
        expect(
            api.isVisibleInCatalog(
                buildProduct(8, {
                    status: "active",
                    published_at: "2026-02-28T12:00:00Z",
                }),
            ),
        ).toBe(true);

        await api.refreshCatalogCache();

        expect(refreshAdminCatalogCacheMock).toHaveBeenCalledTimes(1);
        expect(showSuccess).toHaveBeenCalledWith(
            "Catalog cache refreshed (version 42). Storefront browser cache may take up to 60 seconds.",
        );
        expect(api.isRefreshingCatalogCache.value).toBe(false);

        scope.stop();
    });
});
