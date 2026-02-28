import { beforeEach, describe, expect, it, vi } from "vitest";
import { effectScope, reactive, ref } from "vue";

import { useAdminProductCrudMutations } from "@/composables/admin/products/useAdminProductCrudMutations";
import type { ExecuteAdminMutation } from "@/composables/useAdminMutation";
import type { AdminProduct } from "@/types/admin-products";
import type { ProductFormState } from "@/validators/admin/products";

vi.mock("@/api/admin/products", () => ({
    createAdminProduct: vi.fn(),
    deleteAdminProduct: vi.fn(),
    updateAdminProduct: vi.fn(),
}));

import { createAdminProduct, deleteAdminProduct, updateAdminProduct } from "@/api/admin/products";

const createAdminProductMock = createAdminProduct as unknown as ReturnType<typeof vi.fn>;
const deleteAdminProductMock = deleteAdminProduct as unknown as ReturnType<typeof vi.fn>;
const updateAdminProductMock = updateAdminProduct as unknown as ReturnType<typeof vi.fn>;

const buildProduct = (id: number): AdminProduct => ({
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
});

const createValidForm = (): ProductFormState => ({
    sku: "SKU-1",
    name: "Product 1",
    slug: "product-1",
    short_description: "",
    description: "",
    status: "draft",
    is_featured: false,
    category_id: "",
    brand: "",
    weight_grams: "",
    meta_title: "",
    meta_description: "",
    published_at: "",
    variants: [
        {
            local_id: 1,
            id: null,
            sku: "SKU-1-A",
            name: "Variant 1",
            price: "10",
            compare_at_price: "",
            currency: "USD",
            is_active: true,
            attributes_json: "{}",
            inventory_quantity: "5",
            inventory_reserved_quantity: "0",
            inventory_low_stock_threshold: "3",
        },
    ],
});

const createExecuteMutation = (): ExecuteAdminMutation => {
    return async (options) => {
        if (options.clearNotice ?? true) {
            // no-op for isolated mutation tests
        }

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

beforeEach(() => {
    vi.clearAllMocks();
});

describe("useAdminProductCrudMutations", () => {
    it("creates product, resets page to first, and reloads list", async () => {
        const showSuccess = vi.fn();
        const showError = vi.fn();
        const loadProducts = vi.fn(async () => {});
        createAdminProductMock.mockResolvedValue(undefined);

        const scope = effectScope();
        const api = scope.run(() =>
            useAdminProductCrudMutations({
                query: {
                    products: ref([buildProduct(1)]),
                    page: ref(3),
                    loadProducts,
                },
                formState: {
                    editingId: ref<number | null>(null),
                    form: reactive(createValidForm()),
                    resetFormKeepNotice: vi.fn(),
                },
                executeMutation: createExecuteMutation(),
                notice: {
                    showSuccess,
                    showError,
                },
                canDeleteProducts: ref(true),
                uiEffects: {
                    confirm: vi.fn(async () => true),
                    scrollToTop: vi.fn(),
                },
            }),
        );

        expect(api).not.toBeNull();
        if (!api) {
            scope.stop();
            return;
        }

        await api.submitProduct();

        expect(createAdminProductMock).toHaveBeenCalledTimes(1);
        expect(updateAdminProductMock).not.toHaveBeenCalled();
        expect(loadProducts).toHaveBeenCalledWith(1);
        expect(showSuccess).toHaveBeenCalledWith("Product created successfully.");
        expect(showError).not.toHaveBeenCalled();
        expect(api.isSubmitting.value).toBe(false);

        scope.stop();
    });

    it("deletes last item on page and falls back to previous page", async () => {
        const showSuccess = vi.fn();
        const showError = vi.fn();
        const loadProducts = vi.fn(async () => {});
        const resetFormKeepNotice = vi.fn();
        const confirm = vi.fn(async () => true);
        deleteAdminProductMock.mockResolvedValue(undefined);

        const scope = effectScope();
        const api = scope.run(() =>
            useAdminProductCrudMutations({
                query: {
                    products: ref([buildProduct(2)]),
                    page: ref(2),
                    loadProducts,
                },
                formState: {
                    editingId: ref<number | null>(2),
                    form: reactive(createValidForm()),
                    resetFormKeepNotice,
                },
                executeMutation: createExecuteMutation(),
                notice: {
                    showSuccess,
                    showError,
                },
                canDeleteProducts: ref(true),
                uiEffects: {
                    confirm,
                    scrollToTop: vi.fn(),
                },
            }),
        );

        expect(api).not.toBeNull();
        if (!api) {
            scope.stop();
            return;
        }

        await api.removeProduct(buildProduct(2));

        expect(confirm).toHaveBeenCalledWith('Delete product "Product 2"?');
        expect(deleteAdminProductMock).toHaveBeenCalledWith(2);
        expect(loadProducts).toHaveBeenCalledWith(1);
        expect(resetFormKeepNotice).toHaveBeenCalledTimes(1);
        expect(showSuccess).toHaveBeenCalledWith("Product deleted.");
        expect(showError).not.toHaveBeenCalled();
        expect(api.isDeletingId.value).toBeNull();

        scope.stop();
    });

    it("rejects delete when caller has no admin rights", async () => {
        const showSuccess = vi.fn();
        const showError = vi.fn();
        const loadProducts = vi.fn(async () => {});
        const confirm = vi.fn(async () => true);

        const scope = effectScope();
        const api = scope.run(() =>
            useAdminProductCrudMutations({
                query: {
                    products: ref([buildProduct(4)]),
                    page: ref(1),
                    loadProducts,
                },
                formState: {
                    editingId: ref<number | null>(null),
                    form: reactive(createValidForm()),
                    resetFormKeepNotice: vi.fn(),
                },
                executeMutation: createExecuteMutation(),
                notice: {
                    showSuccess,
                    showError,
                },
                canDeleteProducts: ref(false),
                uiEffects: {
                    confirm,
                    scrollToTop: vi.fn(),
                },
            }),
        );

        expect(api).not.toBeNull();
        if (!api) {
            scope.stop();
            return;
        }

        await api.removeProduct(buildProduct(4));

        expect(showError).toHaveBeenCalledWith("Only admin can delete products.");
        expect(confirm).not.toHaveBeenCalled();
        expect(deleteAdminProductMock).not.toHaveBeenCalled();
        expect(showSuccess).not.toHaveBeenCalled();

        scope.stop();
    });
});
