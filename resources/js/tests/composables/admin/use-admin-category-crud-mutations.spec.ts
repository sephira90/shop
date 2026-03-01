import { beforeEach, describe, expect, it, vi } from "vitest";
import { effectScope, reactive, ref } from "vue";

import { useAdminCategoryCrudMutations } from "@/composables/admin/categories/useAdminCategoryCrudMutations";
import type { ExecuteAdminMutation } from "@/composables/useAdminMutation";
import type { AdminCategory } from "@/types/admin-categories";
import type { CategoryFormState } from "@/validators/admin/categories";

vi.mock("@/api/admin/categories", () => ({
    listAdminCategoryOptions: vi.fn(),
    createAdminCategory: vi.fn(),
    updateAdminCategory: vi.fn(),
    deleteAdminCategory: vi.fn(),
}));

import {
    createAdminCategory,
    deleteAdminCategory,
    updateAdminCategory,
} from "@/api/admin/categories";

const createAdminCategoryMock = createAdminCategory as unknown as ReturnType<typeof vi.fn>;
const updateAdminCategoryMock = updateAdminCategory as unknown as ReturnType<typeof vi.fn>;
const deleteAdminCategoryMock = deleteAdminCategory as unknown as ReturnType<typeof vi.fn>;

const buildCategory = (id: number): AdminCategory => ({
    id,
    parent_id: null,
    name: `Category ${id}`,
    slug: `category-${id}`,
    description: null,
    meta_title: null,
    meta_description: null,
    is_active: true,
    sort_order: id,
    parent: null,
    children_count: 0,
    products_count: 0,
});

const createCategoryForm = (): CategoryFormState => ({
    parent_id: "",
    name: " Shoes ",
    slug: " shoes ",
    description: "",
    meta_title: "",
    meta_description: "",
    is_active: true,
    sort_order: "10",
});

const createExecuteMutation = (): ExecuteAdminMutation => {
    return async (options) => {
        options.setPending?.(true);

        try {
            const result = await options.run();
            await options.onSuccess?.(result);
            return result;
        } catch (error: unknown) {
            await options.onError?.(error);
            return null;
        } finally {
            options.setPending?.(false);
        }
    };
};

beforeEach(() => {
    vi.clearAllMocks();
});

describe("useAdminCategoryCrudMutations", () => {
    it("creates category, resets page to first, and reloads list", async () => {
        const showSuccess = vi.fn();
        const showError = vi.fn();
        const loadCategories = vi.fn(async () => {});
        const loadParentOptions = vi.fn(async () => {});
        createAdminCategoryMock.mockResolvedValue(undefined);

        const scope = effectScope();
        const api = scope.run(() =>
            useAdminCategoryCrudMutations({
                query: {
                    categories: ref([buildCategory(1)]),
                    page: ref(4),
                    loadCategories,
                    loadParentOptions,
                },
                formState: {
                    editingId: ref<number | null>(null),
                    form: reactive(createCategoryForm()),
                    resetFormKeepNotice: vi.fn(),
                },
                executeMutation: createExecuteMutation(),
                notice: {
                    showSuccess,
                    showError,
                },
                canDeleteCategories: ref(true),
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

        await api.submitCategory();

        expect(createAdminCategoryMock).toHaveBeenCalledWith(
            expect.objectContaining({
                name: "Shoes",
                slug: "shoes",
            }),
        );
        expect(updateAdminCategoryMock).not.toHaveBeenCalled();
        expect(loadCategories).toHaveBeenCalledWith(1);
        expect(loadParentOptions).toHaveBeenCalledTimes(1);
        expect(showSuccess).toHaveBeenCalledWith("Category created successfully.");
        expect(showError).not.toHaveBeenCalled();

        scope.stop();
    });

    it("updates category and keeps current page", async () => {
        const showSuccess = vi.fn();
        const showError = vi.fn();
        const loadCategories = vi.fn(async () => {});
        const loadParentOptions = vi.fn(async () => {});
        updateAdminCategoryMock.mockResolvedValue(undefined);

        const scope = effectScope();
        const api = scope.run(() =>
            useAdminCategoryCrudMutations({
                query: {
                    categories: ref([buildCategory(7)]),
                    page: ref(3),
                    loadCategories,
                    loadParentOptions,
                },
                formState: {
                    editingId: ref<number | null>(7),
                    form: reactive(createCategoryForm()),
                    resetFormKeepNotice: vi.fn(),
                },
                executeMutation: createExecuteMutation(),
                notice: {
                    showSuccess,
                    showError,
                },
                canDeleteCategories: ref(true),
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

        await api.submitCategory();

        expect(updateAdminCategoryMock).toHaveBeenCalledWith(
            7,
            expect.objectContaining({
                name: "Shoes",
                slug: "shoes",
            }),
        );
        expect(loadCategories).toHaveBeenCalledWith(3);
        expect(loadParentOptions).toHaveBeenCalledTimes(1);
        expect(showSuccess).toHaveBeenCalledWith("Category updated successfully.");
        expect(showError).not.toHaveBeenCalled();

        scope.stop();
    });

    it("rejects delete when caller has no admin rights", async () => {
        const showSuccess = vi.fn();
        const showError = vi.fn();
        const confirm = vi.fn(async () => true);

        const scope = effectScope();
        const api = scope.run(() =>
            useAdminCategoryCrudMutations({
                query: {
                    categories: ref([buildCategory(4)]),
                    page: ref(1),
                    loadCategories: vi.fn(async () => {}),
                    loadParentOptions: vi.fn(async () => {}),
                },
                formState: {
                    editingId: ref<number | null>(null),
                    form: reactive(createCategoryForm()),
                    resetFormKeepNotice: vi.fn(),
                },
                executeMutation: createExecuteMutation(),
                notice: {
                    showSuccess,
                    showError,
                },
                canDeleteCategories: ref(false),
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

        await api.removeCategory(buildCategory(4));

        expect(showError).toHaveBeenCalledWith("Only admin can delete categories.");
        expect(confirm).not.toHaveBeenCalled();
        expect(deleteAdminCategoryMock).not.toHaveBeenCalled();
        expect(showSuccess).not.toHaveBeenCalled();

        scope.stop();
    });

    it("deletes last category on page and falls back to previous page", async () => {
        const showSuccess = vi.fn();
        const showError = vi.fn();
        const loadCategories = vi.fn(async () => {});
        const loadParentOptions = vi.fn(async () => {});
        const resetFormKeepNotice = vi.fn();
        const confirm = vi.fn(async () => true);
        deleteAdminCategoryMock.mockResolvedValue(undefined);

        const scope = effectScope();
        const api = scope.run(() =>
            useAdminCategoryCrudMutations({
                query: {
                    categories: ref([buildCategory(5)]),
                    page: ref(2),
                    loadCategories,
                    loadParentOptions,
                },
                formState: {
                    editingId: ref<number | null>(5),
                    form: reactive(createCategoryForm()),
                    resetFormKeepNotice,
                },
                executeMutation: createExecuteMutation(),
                notice: {
                    showSuccess,
                    showError,
                },
                canDeleteCategories: ref(true),
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

        await api.removeCategory(buildCategory(5));

        expect(confirm).toHaveBeenCalledWith('Delete category "Category 5"?');
        expect(deleteAdminCategoryMock).toHaveBeenCalledWith(5);
        expect(loadCategories).toHaveBeenCalledWith(1);
        expect(loadParentOptions).toHaveBeenCalledTimes(1);
        expect(resetFormKeepNotice).toHaveBeenCalledTimes(1);
        expect(showSuccess).toHaveBeenCalledWith("Category deleted.");
        expect(showError).not.toHaveBeenCalled();

        scope.stop();
    });
});
