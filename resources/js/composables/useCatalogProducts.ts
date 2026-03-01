import { reactive, ref, watch } from "vue";
import { useRoute, useRouter, type LocationQueryRaw } from "vue-router";

import { listCatalogProducts } from "@/api/catalog";
import { useApiError } from "@/composables/useApiError";
import {
    buildCatalogListParams,
    buildCatalogRouteQuery,
    isSameCatalogRouteQuery,
    parseCatalogFiltersFromRouteQuery,
    type CatalogFilters,
} from "@/queries/catalog";
import type { CatalogProduct } from "@/types/catalog";

interface CatalogProductsRouteLike {
    query: Record<string, unknown>;
}

interface CatalogProductsRouterLike {
    replace: (location: { query: LocationQueryRaw }) => Promise<unknown> | unknown;
}

interface UseCatalogProductsOptions {
    route?: CatalogProductsRouteLike;
    router?: CatalogProductsRouterLike;
}

export const useCatalogProducts = (options: UseCatalogProductsOptions = {}) => {
    const route = options.route ?? useRoute();
    const router = options.router ?? useRouter();
    const { parseApiError } = useApiError();
    const products = ref<CatalogProduct[]>([]);
    const isLoading = ref(false);
    const loadError = ref("");
    const filters = reactive<CatalogFilters>(parseCatalogFiltersFromRouteQuery(route.query));
    let activeRequestId = 0;

    const loadProducts = async (): Promise<void> => {
        const requestId = ++activeRequestId;
        isLoading.value = true;
        loadError.value = "";

        try {
            const response = await listCatalogProducts(buildCatalogListParams(filters));

            if (requestId !== activeRequestId) {
                return;
            }

            products.value = response.data;
        } catch (error: unknown) {
            if (requestId !== activeRequestId) {
                return;
            }

            products.value = [];
            loadError.value = parseApiError(error, "Unable to load catalog products.");
        } finally {
            if (requestId === activeRequestId) {
                isLoading.value = false;
            }
        }
    };

    const applyFilters = async (): Promise<void> => {
        const nextQuery = buildCatalogRouteQuery(filters);

        if (isSameCatalogRouteQuery(route.query, nextQuery)) {
            await loadProducts();
            return;
        }

        await router.replace({
            query: nextQuery,
        });
    };

    watch(
        () => route.query,
        (query) => {
            const parsed = parseCatalogFiltersFromRouteQuery(query);
            filters.q = parsed.q;
            filters.sort = parsed.sort;
            void loadProducts();
        },
        { immediate: true },
    );

    return {
        products,
        filters,
        isLoading,
        loadError,
        loadProducts,
        applyFilters,
    };
};
