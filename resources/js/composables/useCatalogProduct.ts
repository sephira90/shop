import { computed, ref, watch } from "vue";
import { useRoute } from "vue-router";

import { getCatalogProductBySlug } from "@/api/catalog";
import { useApiError } from "@/composables/useApiError";
import type { CatalogProduct, CatalogProductVariant } from "@/types/catalog";

interface CatalogProductRouteLike {
    params: Record<string, unknown>;
}

interface UseCatalogProductOptions {
    route?: CatalogProductRouteLike;
}

const resolveRouteSlug = (value: unknown): string => {
    if (Array.isArray(value)) {
        const first = value.find((item) => typeof item === "string");

        return typeof first === "string" ? first.trim() : "";
    }

    return typeof value === "string" ? value.trim() : "";
};

export const useCatalogProduct = (options: UseCatalogProductOptions = {}) => {
    const route = options.route ?? useRoute();
    const { parseApiError } = useApiError();
    const product = ref<CatalogProduct | null>(null);
    const selectedVariantId = ref<number | null>(null);
    const isLoading = ref(false);
    const loadError = ref("");
    let activeRequestId = 0;

    const selectedVariant = computed<CatalogProductVariant | null>(() => {
        return (
            product.value?.variants.find((variant) => variant.id === selectedVariantId.value) ??
            null
        );
    });

    const loadProduct = async (slug: string): Promise<void> => {
        const requestId = ++activeRequestId;
        isLoading.value = true;
        loadError.value = "";

        try {
            const loadedProduct = await getCatalogProductBySlug(slug);

            if (requestId !== activeRequestId) {
                return;
            }

            if (!loadedProduct) {
                product.value = null;
                selectedVariantId.value = null;
                loadError.value = "Product is unavailable right now.";
                return;
            }

            product.value = loadedProduct;
            selectedVariantId.value = loadedProduct.variants[0]?.id ?? null;
        } catch (error: unknown) {
            if (requestId !== activeRequestId) {
                return;
            }

            product.value = null;
            selectedVariantId.value = null;
            loadError.value = parseApiError(error, "Product is unavailable right now.");
        } finally {
            if (requestId === activeRequestId) {
                isLoading.value = false;
            }
        }
    };

    watch(
        () => route.params.slug,
        (slug) => {
            const normalizedSlug = resolveRouteSlug(slug);

            if (normalizedSlug === "") {
                product.value = null;
                selectedVariantId.value = null;
                loadError.value = "Product is unavailable right now.";
                isLoading.value = false;

                return;
            }

            void loadProduct(normalizedSlug);
        },
        { immediate: true },
    );

    return {
        product,
        selectedVariantId,
        selectedVariant,
        isLoading,
        loadError,
        loadProduct,
    };
};
