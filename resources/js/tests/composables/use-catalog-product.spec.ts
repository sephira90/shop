import { beforeEach, describe, expect, it, vi } from "vitest";
import { effectScope, nextTick, reactive } from "vue";

import { getCatalogProductBySlug } from "@/api/catalog";
import { useCatalogProduct } from "@/composables/useCatalogProduct";
import type { CatalogProduct } from "@/types/catalog";

vi.mock("@/api/catalog", () => ({
    getCatalogProductBySlug: vi.fn(),
}));

const getCatalogProductBySlugMock = vi.mocked(getCatalogProductBySlug);

const buildProduct = (id = 1, slug = "trail-boots"): CatalogProduct => ({
    id,
    name: `Product ${id}`,
    slug,
    short_description: "Short description",
    description: "Full description",
    variants: [
        {
            id: id * 10,
            sku: `SKU-${id}`,
            name: "Default",
            price: 19.9 + id,
            currency: "USD",
            is_active: true,
        },
        {
            id: id * 10 + 1,
            sku: `SKU-${id}-2`,
            name: "Secondary",
            price: 29.9 + id,
            currency: "USD",
            is_active: true,
        },
    ],
});

const flushAsync = async (): Promise<void> => {
    await Promise.resolve();
    await nextTick();
    await Promise.resolve();
};

const createDeferred = <T>() => {
    let resolve!: (value: T) => void;
    const promise = new Promise<T>((innerResolve) => {
        resolve = innerResolve;
    });

    return { promise, resolve };
};

describe("useCatalogProduct", () => {
    let route: { params: Record<string, unknown> };

    beforeEach(() => {
        vi.clearAllMocks();
        route = reactive({
            params: {},
        }) as { params: Record<string, unknown> };
    });

    it("loads product from route slug and selects the first variant", async () => {
        const product = buildProduct();
        route.params = {
            slug: "trail-boots",
        };
        getCatalogProductBySlugMock.mockResolvedValue(product);

        const scope = effectScope();
        const vm = scope.run(() =>
            useCatalogProduct({
                route,
            }),
        );

        expect(vm).not.toBeNull();
        if (!vm) {
            scope.stop();
            return;
        }

        await flushAsync();

        expect(getCatalogProductBySlugMock).toHaveBeenCalledWith("trail-boots");
        expect(vm.product.value?.slug).toBe("trail-boots");
        expect(vm.selectedVariantId.value).toBe(product.variants[0]?.id ?? null);
        expect(vm.selectedVariant.value?.id).toBe(product.variants[0]?.id ?? null);

        scope.stop();
    });

    it("returns unavailable state when slug is missing", async () => {
        route.params = {
            slug: [],
        };

        const scope = effectScope();
        const vm = scope.run(() =>
            useCatalogProduct({
                route,
            }),
        );

        expect(vm).not.toBeNull();
        if (!vm) {
            scope.stop();
            return;
        }

        await flushAsync();

        expect(getCatalogProductBySlugMock).not.toHaveBeenCalled();
        expect(vm.product.value).toBeNull();
        expect(vm.selectedVariantId.value).toBeNull();
        expect(vm.loadError.value).toBe("Product is unavailable right now.");

        scope.stop();
    });

    it("ignores stale product responses", async () => {
        const firstResponse = createDeferred<CatalogProduct | null>();
        const secondResponse = createDeferred<CatalogProduct | null>();
        const staleProduct = buildProduct(1, "stale-product");
        const freshProduct = buildProduct(2, "fresh-product");

        route.params = {
            slug: "stale-product",
        };
        getCatalogProductBySlugMock
            .mockReturnValueOnce(firstResponse.promise)
            .mockReturnValueOnce(secondResponse.promise);

        const scope = effectScope();
        const vm = scope.run(() =>
            useCatalogProduct({
                route,
            }),
        );

        expect(vm).not.toBeNull();
        if (!vm) {
            scope.stop();
            return;
        }

        await nextTick();

        route.params = {
            slug: "fresh-product",
        };
        await nextTick();

        secondResponse.resolve(freshProduct);
        await flushAsync();

        firstResponse.resolve(staleProduct);
        await flushAsync();

        expect(vm.product.value?.slug).toBe("fresh-product");
        expect(vm.selectedVariantId.value).toBe(freshProduct.variants[0]?.id ?? null);
        expect(vm.loadError.value).toBe("");

        scope.stop();
    });
});
