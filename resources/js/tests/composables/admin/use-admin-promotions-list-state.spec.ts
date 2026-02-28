import { describe, expect, it, vi } from "vitest";
import { effectScope, ref } from "vue";

import { useAdminPromotionsListState } from "@/composables/admin/promotions/useAdminPromotionsListState";
import type { AdminPromotionRouteFilters } from "@/queries/admin/promotions";
import type {
    Promotion,
    PromotionListParams,
    PromotionStatusFilter,
} from "@/types/admin-promotions";

vi.mock("@/api/admin/promotions", () => ({
    listPromotions: vi.fn(),
}));

import { listPromotions } from "@/api/admin/promotions";

const listPromotionsMock = listPromotions as unknown as ReturnType<typeof vi.fn>;

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

const createFilterState = () => {
    const searchQuery = ref("");
    const statusFilter = ref<PromotionStatusFilter>("all");

    return {
        initialPage: 1,
        searchQuery,
        statusFilter,
        buildListParams: (targetPage: number): PromotionListParams => {
            const params: PromotionListParams = {
                page: targetPage,
            };
            const query = searchQuery.value.trim();

            if (query !== "") {
                params.q = query;
            }

            if (statusFilter.value !== "all") {
                params.is_active = statusFilter.value === "active";
            }

            return params;
        },
        filterSource: () =>
            [searchQuery.value, statusFilter.value] as [string, PromotionStatusFilter],
        applyParsedFilters: (parsed: AdminPromotionRouteFilters) => {
            searchQuery.value = parsed.searchQuery;
            statusFilter.value = parsed.statusFilter;

            return parsed.page;
        },
        readFiltersForPage: (targetPage: number) => ({
            searchQuery: searchQuery.value,
            statusFilter: statusFilter.value,
            page: targetPage,
        }),
    };
};

describe("useAdminPromotionsListState", () => {
    it("loads list payload and syncs selection callback", async () => {
        const clearNotice = vi.fn();
        const showApiError = vi.fn();
        const syncSelectionWithPromotions = vi.fn();
        const clearSelection = vi.fn();
        listPromotionsMock.mockResolvedValue({
            data: [buildPromotion(2)],
            meta: {
                current_page: 2,
                last_page: 5,
                per_page: 30,
                total: 150,
            },
        });

        const scope = effectScope();
        const api = scope.run(() => {
            const filterState = createFilterState();
            filterState.searchQuery.value = "  vip  ";
            filterState.statusFilter.value = "active";

            return useAdminPromotionsListState({
                notice: {
                    clearNotice,
                    showApiError,
                },
                filterState,
                selectionState: {
                    syncSelectionWithPromotions,
                    clearSelection,
                },
            });
        });

        expect(api).not.toBeNull();
        if (!api) {
            scope.stop();
            return;
        }

        await api.loadPromotions(2);

        expect(listPromotionsMock).toHaveBeenCalledWith({
            page: 2,
            q: "vip",
            is_active: true,
        });
        expect(clearNotice).toHaveBeenCalledTimes(1);
        expect(syncSelectionWithPromotions).toHaveBeenCalledWith([buildPromotion(2)]);
        expect(api.promotions.value).toEqual([buildPromotion(2)]);
        expect(api.page.value).toBe(2);
        expect(showApiError).not.toHaveBeenCalled();
        expect(clearSelection).not.toHaveBeenCalled();

        scope.stop();
    });

    it("clears selection and reports notice on list failure", async () => {
        const clearNotice = vi.fn();
        const showApiError = vi.fn();
        const syncSelectionWithPromotions = vi.fn();
        const clearSelection = vi.fn();
        listPromotionsMock.mockRejectedValue(new Error("Failed to load"));

        const scope = effectScope();
        const api = scope.run(() =>
            useAdminPromotionsListState({
                notice: {
                    clearNotice,
                    showApiError,
                },
                filterState: createFilterState(),
                selectionState: {
                    syncSelectionWithPromotions,
                    clearSelection,
                },
            }),
        );

        expect(api).not.toBeNull();
        if (!api) {
            scope.stop();
            return;
        }

        await api.loadPromotions(3);

        expect(clearSelection).toHaveBeenCalledTimes(1);
        expect(syncSelectionWithPromotions).not.toHaveBeenCalled();
        expect(showApiError).toHaveBeenCalledTimes(1);
        expect(api.promotions.value).toEqual([]);
        expect(api.page.value).toBe(1);

        scope.stop();
    });
});
