import { beforeEach, describe, expect, it, vi } from "vitest";
import { effectScope, ref } from "vue";

import { useAdminPromotionCrudMutations } from "@/composables/admin/promotions/useAdminPromotionCrudMutations";
import type { ExecuteAdminMutation } from "@/composables/useAdminMutation";
import type { Promotion, PromotionFormState } from "@/types/admin-promotions";

vi.mock("@/api/admin/promotions", () => ({
    createPromotion: vi.fn(),
    updatePromotion: vi.fn(),
    deletePromotion: vi.fn(),
}));

import { createPromotion, deletePromotion, updatePromotion } from "@/api/admin/promotions";

const createPromotionMock = createPromotion as unknown as ReturnType<typeof vi.fn>;
const updatePromotionMock = updatePromotion as unknown as ReturnType<typeof vi.fn>;
const deletePromotionMock = deletePromotion as unknown as ReturnType<typeof vi.fn>;

const buildPromotion = (id: number): Promotion => ({
    id,
    name: `Campaign ${id}`,
    code: `CODE-${id}`,
    type: "percent",
    value: 10,
    is_active: true,
    usage_limit: null,
    usage_count: 0,
    starts_at: null,
    ends_at: null,
    coupons: [],
});

const createPromotionForm = (): PromotionFormState => ({
    name: "Spring campaign",
    code: " spring10 ",
    type: "percent",
    value: 10,
    is_active: true,
    starts_at: "",
    ends_at: "",
    usage_limit: "",
    coupon_is_active: true,
    coupon_max_redemptions: "",
    coupon_expires_at: "",
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

describe("useAdminPromotionCrudMutations", () => {
    it("creates promotion and reloads first page", async () => {
        const showSuccess = vi.fn();
        const loadPromotions = vi.fn(async () => {});
        const selectedPromotionId = ref<number | null>(null);
        createPromotionMock.mockResolvedValue(undefined);

        const scope = effectScope();
        const api = scope.run(() =>
            useAdminPromotionCrudMutations({
                query: {
                    promotions: ref([buildPromotion(1)]),
                    page: ref(4),
                    loadPromotions,
                    selectedPromotionId,
                },
                formState: {
                    editingPromotionId: ref<number | null>(null),
                    promotionForm: ref(createPromotionForm()),
                    resetPromotionFormKeepNotice: vi.fn(),
                },
                executeMutation: createExecuteMutation(),
                notice: {
                    showSuccess,
                },
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

        await api.submitPromotion();

        expect(createPromotionMock).toHaveBeenCalledWith(
            expect.objectContaining({
                name: "Spring campaign",
                code: "SPRING10",
            }),
        );
        expect(updatePromotionMock).not.toHaveBeenCalled();
        expect(loadPromotions).toHaveBeenCalledWith(1);
        expect(showSuccess).toHaveBeenCalledWith("Campaign created.");

        scope.stop();
    });

    it("updates promotion and keeps selected promotion synchronized", async () => {
        const showSuccess = vi.fn();
        const loadPromotions = vi.fn(async () => {});
        const selectedPromotionId = ref<number | null>(2);
        updatePromotionMock.mockResolvedValue(undefined);

        const scope = effectScope();
        const api = scope.run(() =>
            useAdminPromotionCrudMutations({
                query: {
                    promotions: ref([buildPromotion(7)]),
                    page: ref(3),
                    loadPromotions,
                    selectedPromotionId,
                },
                formState: {
                    editingPromotionId: ref<number | null>(7),
                    promotionForm: ref(createPromotionForm()),
                    resetPromotionFormKeepNotice: vi.fn(),
                },
                executeMutation: createExecuteMutation(),
                notice: {
                    showSuccess,
                },
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

        await api.submitPromotion();

        expect(updatePromotionMock).toHaveBeenCalledWith(
            7,
            expect.objectContaining({
                code: "SPRING10",
            }),
        );
        expect(loadPromotions).toHaveBeenCalledWith(3);
        expect(selectedPromotionId.value).toBe(7);
        expect(showSuccess).toHaveBeenCalledWith("Campaign updated.");

        scope.stop();
    });

    it("deletes last promotion on page and falls back to previous page", async () => {
        const showSuccess = vi.fn();
        const loadPromotions = vi.fn(async () => {});
        const resetPromotionFormKeepNotice = vi.fn();
        const confirm = vi.fn(async () => true);
        deletePromotionMock.mockResolvedValue(undefined);

        const scope = effectScope();
        const api = scope.run(() =>
            useAdminPromotionCrudMutations({
                query: {
                    promotions: ref([buildPromotion(9)]),
                    page: ref(2),
                    loadPromotions,
                    selectedPromotionId: ref<number | null>(9),
                },
                formState: {
                    editingPromotionId: ref<number | null>(9),
                    promotionForm: ref(createPromotionForm()),
                    resetPromotionFormKeepNotice,
                },
                executeMutation: createExecuteMutation(),
                notice: {
                    showSuccess,
                },
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

        await api.removePromotion(buildPromotion(9));

        expect(confirm).toHaveBeenCalledWith('Delete campaign "Campaign 9"?');
        expect(deletePromotionMock).toHaveBeenCalledWith(9);
        expect(loadPromotions).toHaveBeenCalledWith(1);
        expect(resetPromotionFormKeepNotice).toHaveBeenCalledTimes(1);
        expect(showSuccess).toHaveBeenCalledWith("Campaign deleted.");

        scope.stop();
    });
});
