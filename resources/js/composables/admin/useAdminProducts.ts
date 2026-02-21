import { computed, reactive, ref } from 'vue';

import { listAdminCategories } from '@/api/admin/categories';
import {
    createAdminProduct,
    deleteAdminProduct,
    listAdminProducts,
    refreshAdminCatalogCache,
    updateAdminProduct,
} from '@/api/admin/products';
import { useAdminMutation } from '@/composables/useAdminMutation';
import { useAdminNotice } from '@/composables/useAdminNotice';
import { useServerPaginatedList } from '@/composables/useServerPaginatedList';
import { buildAdminProductListParams } from '@/queries/admin/products';
import { useAuthStore } from '@/stores/auth';
import type { AdminCategory } from '@/types/admin-categories';
import type {
    AdminProduct,
    AdminProductListParams,
    AdminProductCategory,
    ProductStatus,
    ProductVariant,
    ProductVariantForm,
} from '@/types/admin-products';
import { normalizeDatetimeForInput } from '@/utils/datetime';
import {
    buildProductMutationPayload,
    buildProductMutationPayloadFromProduct,
    createProductFormState,
    type ProductFormState,
} from '@/validators/admin/products';

const toCategoryOption = (category: AdminCategory): AdminProductCategory => ({
    id: category.id,
    name: category.name,
    slug: category.slug,
});

export const useAdminProducts = () => {
    const authStore = useAuthStore();
    const categories = ref<AdminProductCategory[]>([]);
    const searchQuery = ref('');
    const { notice, clearNotice, showSuccess, showError, showApiError } = useAdminNotice();
    const { executeMutation } = useAdminMutation({
        clearNotice,
        showApiError,
    });
    const isLoadingCategories = ref(false);
    const isSubmitting = ref(false);
    const isDeletingId = ref<number | null>(null);
    const isVisibilityUpdatingId = ref<number | null>(null);
    const isRefreshingCatalogCache = ref(false);
    const editingId = ref<number | null>(null);
    const canDeleteProducts = computed<boolean>(() => authStore.hasRole('admin'));
    let nextVariantLocalId = 1;

    const createEmptyVariantForm = (): ProductVariantForm => {
        return {
            local_id: nextVariantLocalId++,
            id: null,
            sku: '',
            name: '',
            price: '0.00',
            compare_at_price: '',
            currency: 'USD',
            is_active: true,
            attributes_json: '{}',
            inventory_quantity: '0',
            inventory_reserved_quantity: '0',
            inventory_low_stock_threshold: '3',
        };
    };

    const formatVariantAttributes = (attributes: Record<string, unknown> | null): string => {
        if (!attributes || typeof attributes !== 'object' || Array.isArray(attributes)) {
            return '{}';
        }

        return JSON.stringify(attributes, null, 2);
    };

    const mapVariantToForm = (variant: ProductVariant): ProductVariantForm => {
        return {
            local_id: nextVariantLocalId++,
            id: variant.id,
            sku: variant.sku,
            name: variant.name,
            price: String(variant.price),
            compare_at_price: variant.compare_at_price !== null ? String(variant.compare_at_price) : '',
            currency: variant.currency,
            is_active: variant.is_active,
            attributes_json: formatVariantAttributes(variant.attributes),
            inventory_quantity: String(variant.inventory?.quantity ?? 0),
            inventory_reserved_quantity: String(variant.inventory?.reserved_quantity ?? 0),
            inventory_low_stock_threshold: String(variant.inventory?.low_stock_threshold ?? 3),
        };
    };

    const form = reactive<ProductFormState>(createProductFormState([createEmptyVariantForm()]));

    const {
        items: products,
        page,
        isLoading,
        meta,
        load: loadProducts,
    } = useServerPaginatedList<AdminProduct, AdminProductListParams>({
        buildParams: (targetPage) =>
            buildAdminProductListParams(targetPage, {
                searchQuery: searchQuery.value,
            }),
        fetchPage: listAdminProducts,
        filterSource: searchQuery,
        debounceMs: 300,
        onLoading: () => {
            clearNotice();
        },
        onError: (error: unknown) => {
            showApiError(error, 'Unable to load products.');
        },
    });

    const filteredProducts = computed<AdminProduct[]>(() => products.value);

    const statusBadgeClass = (status: ProductStatus): string => {
        return `badge--${status}`;
    };

    const clearFormState = (): void => {
        editingId.value = null;
        Object.assign(form, createProductFormState([createEmptyVariantForm()]));
    };

    const resetForm = (): void => {
        clearFormState();
        clearNotice();
    };

    const resetFormKeepNotice = (): void => {
        clearFormState();
    };

    const addVariant = (): void => {
        form.variants.push(createEmptyVariantForm());
    };

    const removeVariant = (index: number): void => {
        if (form.variants.length <= 1) {
            return;
        }

        form.variants.splice(index, 1);
    };

    const isVisibleInCatalog = (product: AdminProduct): boolean => {
        return product.status === 'active' && product.published_at !== null;
    };

    const loadCategories = async (): Promise<void> => {
        await executeMutation<void>({
            setPending: (pending) => {
                isLoadingCategories.value = pending;
            },
            errorMessage: 'Unable to load categories for product form.',
            run: async () => {
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
            },
            onError: (error: unknown) => {
                categories.value = [];
                showApiError(error, 'Unable to load categories for product form.');
            },
        });
    };

    const submitProduct = async (): Promise<void> => {
        await executeMutation<void>({
            setPending: (pending) => {
                isSubmitting.value = pending;
            },
            errorMessage: 'Unable to save product.',
            run: async () => {
                const payload = buildProductMutationPayload(form);

                if (editingId.value) {
                    await updateAdminProduct(editingId.value, payload);
                    showSuccess('Product updated successfully.');
                } else {
                    await createAdminProduct(payload);
                    showSuccess('Product created successfully.');
                    page.value = 1;
                }

                await loadProducts(page.value);
                resetFormKeepNotice();
            },
        });
    };

    const startEdit = (product: AdminProduct): void => {
        editingId.value = product.id;
        form.sku = product.sku;
        form.name = product.name;
        form.slug = product.slug;
        form.short_description = product.short_description ?? '';
        form.description = product.description ?? '';
        form.status = product.status;
        form.is_featured = product.is_featured;
        form.category_id = product.category ? String(product.category.id) : '';
        form.brand = product.brand ?? '';
        form.weight_grams = product.weight_grams !== null ? String(product.weight_grams) : '';
        form.meta_title = product.meta.title ?? '';
        form.meta_description = product.meta.description ?? '';
        form.published_at = normalizeDatetimeForInput(product.published_at);
        form.variants = product.variants.length > 0
            ? product.variants.map((variant) => mapVariantToForm(variant))
            : [createEmptyVariantForm()];
        clearNotice();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    const removeProduct = async (product: AdminProduct): Promise<void> => {
        if (!canDeleteProducts.value) {
            showError('Only admin can delete products.');
            return;
        }

        if (!window.confirm(`Delete product "${product.name}"?`)) {
            return;
        }

        await executeMutation<void>({
            setPending: (pending) => {
                isDeletingId.value = pending ? product.id : null;
            },
            errorMessage: 'Unable to delete product.',
            run: async () => {
                await deleteAdminProduct(product.id);
                showSuccess('Product deleted.');
                const nextPage = products.value.length === 1 && page.value > 1 ? page.value - 1 : page.value;
                await loadProducts(nextPage);
                if (editingId.value === product.id) {
                    resetFormKeepNotice();
                }
            },
        });
    };

    const refreshCatalogCache = async (): Promise<void> => {
        await executeMutation<void>({
            setPending: (pending) => {
                isRefreshingCatalogCache.value = pending;
            },
            errorMessage: 'Unable to refresh catalog cache.',
            run: async () => {
                const nextVersion = await refreshAdminCatalogCache();

                showSuccess(nextVersion > 0
                    ? `Catalog cache refreshed (version ${nextVersion}). Storefront browser cache may take up to 60 seconds.`
                    : 'Catalog cache refreshed. Storefront browser cache may take up to 60 seconds.');
            },
        });
    };

    const toggleCatalogVisibility = async (product: AdminProduct): Promise<void> => {
        await executeMutation<void>({
            setPending: (pending) => {
                isVisibilityUpdatingId.value = pending ? product.id : null;
            },
            errorMessage: 'Unable to change catalog visibility.',
            run: async () => {
                const currentlyVisible = isVisibleInCatalog(product);
                const payload = buildProductMutationPayloadFromProduct(product);
                payload.status = currentlyVisible ? 'draft' : 'active';
                payload.published_at = currentlyVisible ? null : new Date().toISOString();

                await updateAdminProduct(product.id, payload);

                showSuccess(currentlyVisible
                    ? 'Product hidden from catalog.'
                    : 'Product published to catalog. Public cache may refresh within 60 seconds.');

                await loadProducts(page.value);
            },
        });
    };

    return {
        products,
        categories,
        page,
        isLoading,
        isLoadingCategories,
        isSubmitting,
        isDeletingId,
        isVisibilityUpdatingId,
        isRefreshingCatalogCache,
        editingId,
        searchQuery,
        canDeleteProducts,
        meta,
        notice,
        form,
        filteredProducts,
        statusBadgeClass,
        resetForm,
        addVariant,
        removeVariant,
        loadCategories,
        loadProducts,
        submitProduct,
        startEdit,
        removeProduct,
        refreshCatalogCache,
        toggleCatalogVisibility,
        isVisibleInCatalog,
    };
};
