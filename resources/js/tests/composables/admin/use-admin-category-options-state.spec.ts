import { describe, expect, it, vi } from "vitest";
import { effectScope } from "vue";

import { useAdminCategoryOptionsState } from "@/composables/admin/categories/useAdminCategoryOptionsState";
import type { AdminCategoryOption } from "@/types/admin-categories";

vi.mock("@/api/admin/categories", () => ({
    listAdminCategoryOptions: vi.fn(),
}));

import { listAdminCategoryOptions } from "@/api/admin/categories";

const listAdminCategoryOptionsMock = listAdminCategoryOptions as unknown as ReturnType<
    typeof vi.fn
>;

const buildCategoryOption = (id: number, name: string): AdminCategoryOption => ({
    id,
    parent_id: null,
    name,
    slug: `category-${id}`,
    is_active: true,
});

const createDeferred = <TValue>() => {
    let resolve!: (value: TValue | PromiseLike<TValue>) => void;
    let reject!: (reason?: unknown) => void;
    const promise = new Promise<TValue>((promiseResolve, promiseReject) => {
        resolve = promiseResolve;
        reject = promiseReject;
    });

    return {
        promise,
        resolve,
        reject,
    };
};

describe("useAdminCategoryOptionsState", () => {
    it("loads category options without clearing notices and applies exclude_id client-side", async () => {
        const clearNotice = vi.fn();
        const showApiError = vi.fn();
        listAdminCategoryOptionsMock.mockResolvedValue([
            buildCategoryOption(3, "Winter"),
            buildCategoryOption(1, "Autumn"),
            buildCategoryOption(2, "Basics"),
        ]);

        const scope = effectScope();
        const api = scope.run(() =>
            useAdminCategoryOptionsState({
                clearNotice,
                showApiError,
            }),
        );

        expect(api).not.toBeNull();
        if (!api) {
            scope.stop();
            return;
        }

        await api.loadCategoryOptions({
            exclude_id: 1,
        });

        expect(listAdminCategoryOptionsMock).toHaveBeenCalledWith({
            exclude_id: 1,
        });
        expect(clearNotice).not.toHaveBeenCalled();
        expect(showApiError).not.toHaveBeenCalled();
        expect(api.categoryOptions.value).toEqual([
            buildCategoryOption(3, "Winter"),
            buildCategoryOption(2, "Basics"),
        ]);
        expect(api.isLoadingCategoryOptions.value).toBe(false);

        scope.stop();
    });

    it("keeps newest category options when older request resolves later", async () => {
        const showApiError = vi.fn();
        const firstRequest = createDeferred<AdminCategoryOption[]>();
        const secondRequest = createDeferred<AdminCategoryOption[]>();
        listAdminCategoryOptionsMock
            .mockReturnValueOnce(firstRequest.promise)
            .mockReturnValueOnce(secondRequest.promise);

        const scope = effectScope();
        const api = scope.run(() =>
            useAdminCategoryOptionsState({
                clearNotice: vi.fn(),
                showApiError,
            }),
        );

        expect(api).not.toBeNull();
        if (!api) {
            scope.stop();
            return;
        }

        const firstLoad = api.loadCategoryOptions();
        const secondLoad = api.loadCategoryOptions({
            q: "winter",
        });

        secondRequest.resolve([buildCategoryOption(2, "Winter")]);
        await secondLoad;
        expect(api.categoryOptions.value).toEqual([buildCategoryOption(2, "Winter")]);

        firstRequest.resolve([buildCategoryOption(1, "Autumn")]);
        await firstLoad;

        expect(api.categoryOptions.value).toEqual([buildCategoryOption(2, "Winter")]);
        expect(showApiError).not.toHaveBeenCalled();
        expect(api.isLoadingCategoryOptions.value).toBe(false);

        scope.stop();
    });

    it("clears options and reports notice on failure", async () => {
        const clearNotice = vi.fn();
        const showApiError = vi.fn();
        listAdminCategoryOptionsMock.mockRejectedValue(new Error("Failed to load"));

        const scope = effectScope();
        const api = scope.run(() =>
            useAdminCategoryOptionsState({
                clearNotice,
                showApiError,
            }),
        );

        expect(api).not.toBeNull();
        if (!api) {
            scope.stop();
            return;
        }

        await api.loadCategoryOptions();

        expect(clearNotice).not.toHaveBeenCalled();
        expect(showApiError).toHaveBeenCalledTimes(1);
        expect(api.categoryOptions.value).toEqual([]);
        expect(api.isLoadingCategoryOptions.value).toBe(false);

        scope.stop();
    });
});
