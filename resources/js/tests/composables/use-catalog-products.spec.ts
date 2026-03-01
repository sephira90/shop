import { beforeEach, describe, expect, it, vi } from "vitest";
import { effectScope, nextTick, reactive } from "vue";
import type { LocationQueryRaw } from "vue-router";

import { listCatalogProducts } from "@/api/catalog";
import { useCatalogProducts } from "@/composables/useCatalogProducts";
import type { CatalogProduct } from "@/types/catalog";

vi.mock("@/api/catalog", () => ({
    listCatalogProducts: vi.fn(),
}));

const listCatalogProductsMock = vi.mocked(listCatalogProducts);

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
            price: 49.9 + id,
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

describe("useCatalogProducts", () => {
    let route: { query: Record<string, unknown> };
    let replaceCalls: Array<{ query: LocationQueryRaw }>;
    let replaceRoute: (location: { query: LocationQueryRaw }) => Promise<void>;

    beforeEach(() => {
        vi.clearAllMocks();
        route = reactive({
            query: {},
        }) as { query: Record<string, unknown> };
        replaceCalls = [];
        replaceRoute = async ({ query }: { query: LocationQueryRaw }) => {
            replaceCalls.push({
                query,
            });
            route.query = query as Record<string, unknown>;
        };
    });

    it("hydrates filters from route and syncs applyFilters through router", async () => {
        route.query = {
            q: " boots ",
            sort: "price_desc",
        };
        listCatalogProductsMock.mockResolvedValue({
            data: [buildProduct()],
            meta: {} as never,
        });

        const scope = effectScope();
        const vm = scope.run(() =>
            useCatalogProducts({
                route,
                router: {
                    replace: replaceRoute,
                },
            }),
        );

        expect(vm).not.toBeNull();
        if (!vm) {
            scope.stop();
            return;
        }

        await flushAsync();

        expect(vm.filters.q).toBe("boots");
        expect(vm.filters.sort).toBe("price_desc");
        expect(listCatalogProductsMock).toHaveBeenCalledWith({
            q: "boots",
            sort: "price_desc",
        });
        expect(vm.products.value).toHaveLength(1);

        vm.filters.q = " jackets ";
        vm.filters.sort = "name_asc";
        await vm.applyFilters();
        await flushAsync();

        expect(replaceCalls).toEqual([
            {
                query: {
                    q: "jackets",
                    sort: "name_asc",
                },
            },
        ]);
        expect(listCatalogProductsMock).toHaveBeenLastCalledWith({
            q: "jackets",
            sort: "name_asc",
        });

        scope.stop();
    });

    it("ignores stale catalog list responses", async () => {
        const firstResponse = createDeferred<{ data: CatalogProduct[]; meta: never }>();
        const secondResponse = createDeferred<{ data: CatalogProduct[]; meta: never }>();
        const staleProduct = buildProduct(1, "stale-product");
        const freshProduct = buildProduct(2, "fresh-product");

        route.query = { q: "first" };
        listCatalogProductsMock
            .mockReturnValueOnce(firstResponse.promise)
            .mockReturnValueOnce(secondResponse.promise);

        const scope = effectScope();
        const vm = scope.run(() =>
            useCatalogProducts({
                route,
                router: {
                    replace: replaceRoute,
                },
            }),
        );

        expect(vm).not.toBeNull();
        if (!vm) {
            scope.stop();
            return;
        }

        await nextTick();

        route.query = { q: "second" };
        await nextTick();

        secondResponse.resolve({
            data: [freshProduct],
            meta: {} as never,
        });
        await flushAsync();

        firstResponse.resolve({
            data: [staleProduct],
            meta: {} as never,
        });
        await flushAsync();

        expect(vm.products.value).toHaveLength(1);
        expect(vm.products.value[0]?.slug).toBe("fresh-product");
        expect(vm.loadError.value).toBe("");

        scope.stop();
    });
});
