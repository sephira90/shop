import { computed, ref } from "vue";

import { listAdminCategories } from "@/api/admin/categories";
import { listAdminProducts } from "@/api/admin/products";
import type { AdminRouteSyncOptions } from "@/composables/admin/adminRouteSync";
import { useServerPaginatedList } from "@/composables/useServerPaginatedList";
import { useServerListFilters } from "@/composables/useServerListFilters";
import { useRouteSyncedPagination } from "@/composables/useRouteSyncedPagination";
import {
    buildAdminProductListParams,
    buildAdminProductRouteQuery,
    isSameAdminProductRouteQuery,
    parseAdminProductFiltersFromRouteQuery,
} from "@/queries/admin/products";
import type { AdminCategory } from "@/types/admin-categories";
import type {
    AdminProduct,
    AdminProductCategory,
    AdminProductListParams,
} from "@/types/admin-products";

interface AdminProductsQueryNoticeAdapter {
    clearNotice: () => void;
    showApiError: (error: unknown, fallback: string) => void;
}

const toCategoryOption = (category: AdminCategory): AdminProductCategory => ({
    id: category.id,
    name: category.name,
    slug: category.slug,
});

export const useAdminProductsQuery = (
    notice: AdminProductsQueryNoticeAdapter,
    routeSync?: AdminRouteSyncOptions,
) => {
    const initialFilters = routeSync
        ? parseAdminProductFiltersFromRouteQuery(routeSync.route.query)
        : { searchQuery: "", page: 1 };
    const searchQuery = ref(initialFilters.searchQuery);
    const categories = ref<AdminProductCategory[]>([]);
    const isLoadingCategories = ref(false);

    const {
        items: products,
        page,
        isLoading,
        meta,
        load: loadProductsRaw,
    } = useServerPaginatedList<AdminProduct, AdminProductListParams>({
        buildParams: (targetPage) =>
            buildAdminProductListParams(targetPage, {
                searchQuery: searchQuery.value,
            }),
        fetchPage: listAdminProducts,
        ...(routeSync
            ? { initialPage: initialFilters.page }
            : {
                  filterSource: searchQuery,
                  debounceMs: 300,
              }),
        onLoading: () => {
            notice.clearNotice();
        },
        onError: (error: unknown) => {
            notice.showApiError(error, "Unable to load products.");
        },
    });
    const routePagination = useRouteSyncedPagination({
        route: routeSync?.route,
        router: routeSync?.router,
        parseRouteQuery: parseAdminProductFiltersFromRouteQuery,
        buildRouteQuery: buildAdminProductRouteQuery,
        isSameRouteQuery: isSameAdminProductRouteQuery,
        applyParsedFilters: (parsed) => {
            searchQuery.value = parsed.searchQuery;
            page.value = parsed.page;
        },
        readFiltersForPage: (targetPage) => ({
            searchQuery: searchQuery.value,
            page: targetPage,
        }),
        fetchPage: loadProductsRaw,
        immediate: false,
    });
    const loadProducts = async (targetPage = page.value): Promise<void> => {
        await routePagination.load(targetPage);
    };

    if (routeSync) {
        useServerListFilters(searchQuery, () => loadProducts(1), {
            debounceMs: 300,
        });
    }

    const filteredProducts = computed<AdminProduct[]>(() => products.value);

    const loadCategories = async (): Promise<void> => {
        notice.clearNotice();
        isLoadingCategories.value = true;

        try {
            const collected: AdminProductCategory[] = [];
            let currentPage = 1;

            while (true) {
                const response = await listAdminCategories({
                    page: currentPage,
                    per_page: 200,
                });

                collected.push(...response.data.map((category) => toCategoryOption(category)));

                if (response.meta.current_page >= response.meta.last_page) {
                    break;
                }

                currentPage += 1;
            }

            categories.value = collected.sort((left, right) => left.name.localeCompare(right.name));
        } catch (error: unknown) {
            categories.value = [];
            notice.showApiError(error, "Unable to load categories for product form.");
        } finally {
            isLoadingCategories.value = false;
        }
    };

    return {
        products,
        page,
        isLoading,
        meta,
        searchQuery,
        filteredProducts,
        loadProducts,
        categories,
        isLoadingCategories,
        loadCategories,
    };
};
