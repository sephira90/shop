import { ref, type Ref } from "vue";

import { refreshAdminCatalogCache, updateAdminProduct } from "@/api/admin/products";
import { executeAdminActionMutationPipeline } from "@/composables/admin/adminActionMutationPipeline";
import type { AdminSuccessNoticeAdapter } from "@/composables/admin/useAdminMutationContext";
import type { ExecuteAdminMutation } from "@/composables/useAdminMutation";
import type { AdminProduct } from "@/types/admin-products";
import { buildProductMutationPayloadFromProduct } from "@/validators/admin/products";

interface AdminProductsPublishingQueryAdapter {
    page: Ref<number>;
    loadProducts: (targetPage?: number) => Promise<void>;
}

interface UseAdminProductPublishingMutationsOptions {
    query: AdminProductsPublishingQueryAdapter;
    executeMutation: ExecuteAdminMutation;
    notice: AdminSuccessNoticeAdapter;
}

export const useAdminProductPublishingMutations = ({
    query,
    executeMutation,
    notice,
}: UseAdminProductPublishingMutationsOptions) => {
    const isVisibilityUpdatingId = ref<number | null>(null);
    const isRefreshingCatalogCache = ref(false);

    const isVisibleInCatalog = (product: AdminProduct): boolean => {
        return product.status === "active" && product.published_at !== null;
    };

    const refreshCatalogCache = async (): Promise<void> => {
        await executeAdminActionMutationPipeline<number>({
            executeMutation,
            setPending: (pending) => {
                isRefreshingCatalogCache.value = pending;
            },
            errorMessage: "Unable to refresh catalog cache.",
            run: refreshAdminCatalogCache,
            resolveSuccessMessage: (nextVersion) =>
                nextVersion > 0
                    ? `Catalog cache refreshed (version ${nextVersion}). Storefront browser cache may take up to 60 seconds.`
                    : "Catalog cache refreshed. Storefront browser cache may take up to 60 seconds.",
            showSuccess: notice.showSuccess,
        });
    };

    const toggleCatalogVisibility = async (product: AdminProduct): Promise<void> => {
        await executeAdminActionMutationPipeline<boolean>({
            executeMutation,
            setPending: (pending) => {
                isVisibilityUpdatingId.value = pending ? product.id : null;
            },
            errorMessage: "Unable to change catalog visibility.",
            run: async () => {
                const currentlyVisible = isVisibleInCatalog(product);
                const payload = buildProductMutationPayloadFromProduct(product);
                payload.status = currentlyVisible ? "draft" : "active";
                payload.published_at = currentlyVisible ? null : new Date().toISOString();

                await updateAdminProduct(product.id, payload);
                return currentlyVisible;
            },
            resolveSuccessMessage: (currentlyVisible) =>
                currentlyVisible
                    ? "Product hidden from catalog."
                    : "Product published to catalog. Public cache may refresh within 60 seconds.",
            showSuccess: notice.showSuccess,
            afterSuccess: async () => {
                await query.loadProducts(query.page.value);
            },
        });
    };

    return {
        isVisibilityUpdatingId,
        isRefreshingCatalogCache,
        refreshCatalogCache,
        toggleCatalogVisibility,
        isVisibleInCatalog,
    };
};
