import { beforeEach, describe, expect, it, vi } from "vitest";
import { effectScope, ref } from "vue";

import { useAdminPromotionCouponMutations } from "@/composables/admin/promotions/useAdminPromotionCouponMutations";
import type { ExecuteAdminMutation } from "@/composables/useAdminMutation";
import type { Coupon, CouponFormState, Promotion } from "@/types/admin-promotions";

vi.mock("@/api/admin/promotions", () => ({
    createPromotionCoupon: vi.fn(),
    updateCoupon: vi.fn(),
}));

import { createPromotionCoupon, updateCoupon } from "@/api/admin/promotions";

const createPromotionCouponMock = createPromotionCoupon as unknown as ReturnType<typeof vi.fn>;
const updateCouponMock = updateCoupon as unknown as ReturnType<typeof vi.fn>;

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

const buildCoupon = (id: number, isActive: boolean): Coupon => ({
    id,
    code: `CP-${id}`,
    is_active: isActive,
    max_redemptions: null,
    redeemed_count: 0,
    expires_at: null,
});

const createCouponForm = (): CouponFormState => ({
    code: " spring20 ",
    is_active: true,
    max_redemptions: "100",
    expires_at: "",
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

describe("useAdminPromotionCouponMutations", () => {
    it("reports error when coupon create is requested without selected promotion", async () => {
        const showSuccess = vi.fn();
        const showError = vi.fn();

        const scope = effectScope();
        const api = scope.run(() =>
            useAdminPromotionCouponMutations({
                query: {
                    selectedPromotion: ref<Promotion | null>(null),
                    page: ref(1),
                    loadPromotions: vi.fn(async () => {}),
                },
                couponFormState: {
                    couponForm: ref(createCouponForm()),
                    resetCouponForm: vi.fn(),
                },
                executeMutation: createExecuteMutation(),
                notice: {
                    showSuccess,
                    showError,
                },
            }),
        );

        expect(api).not.toBeNull();
        if (!api) {
            scope.stop();
            return;
        }

        await api.createCoupon();

        expect(showError).toHaveBeenCalledWith("Select promotion first.");
        expect(createPromotionCouponMock).not.toHaveBeenCalled();
        expect(showSuccess).not.toHaveBeenCalled();

        scope.stop();
    });

    it("creates coupon and resets coupon form state", async () => {
        const showSuccess = vi.fn();
        const showError = vi.fn();
        const loadPromotions = vi.fn(async () => {});
        const resetCouponForm = vi.fn();
        createPromotionCouponMock.mockResolvedValue(undefined);

        const scope = effectScope();
        const api = scope.run(() =>
            useAdminPromotionCouponMutations({
                query: {
                    selectedPromotion: ref<Promotion | null>(buildPromotion(5)),
                    page: ref(3),
                    loadPromotions,
                },
                couponFormState: {
                    couponForm: ref(createCouponForm()),
                    resetCouponForm,
                },
                executeMutation: createExecuteMutation(),
                notice: {
                    showSuccess,
                    showError,
                },
            }),
        );

        expect(api).not.toBeNull();
        if (!api) {
            scope.stop();
            return;
        }

        await api.createCoupon();

        expect(createPromotionCouponMock).toHaveBeenCalledWith(5, {
            code: "SPRING20",
            is_active: true,
            max_redemptions: 100,
            expires_at: null,
        });
        expect(resetCouponForm).toHaveBeenCalledTimes(1);
        expect(loadPromotions).toHaveBeenCalledWith(3);
        expect(showSuccess).toHaveBeenCalledWith("Coupon created.");
        expect(showError).not.toHaveBeenCalled();

        scope.stop();
    });

    it("toggles coupon active status and reloads list", async () => {
        const showSuccess = vi.fn();
        const showError = vi.fn();
        const loadPromotions = vi.fn(async () => {});
        updateCouponMock.mockResolvedValue(undefined);

        const scope = effectScope();
        const api = scope.run(() =>
            useAdminPromotionCouponMutations({
                query: {
                    selectedPromotion: ref<Promotion | null>(buildPromotion(7)),
                    page: ref(2),
                    loadPromotions,
                },
                couponFormState: {
                    couponForm: ref(createCouponForm()),
                    resetCouponForm: vi.fn(),
                },
                executeMutation: createExecuteMutation(),
                notice: {
                    showSuccess,
                    showError,
                },
            }),
        );

        expect(api).not.toBeNull();
        if (!api) {
            scope.stop();
            return;
        }

        await api.toggleCoupon(buildCoupon(22, true));

        expect(updateCouponMock).toHaveBeenCalledWith(22, {
            is_active: false,
        });
        expect(loadPromotions).toHaveBeenCalledWith(2);
        expect(showSuccess).toHaveBeenCalledWith("Coupon status updated.");
        expect(showError).not.toHaveBeenCalled();

        scope.stop();
    });
});
