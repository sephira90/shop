import { afterEach, describe, expect, it, vi } from "vitest";
import { effectScope, nextTick, reactive } from "vue";

import type { ListResponse } from "@/api/response";
import { useServerPaginatedList } from "@/composables/useServerPaginatedList";

const buildResponse = <TItem>(items: TItem[], page: number): ListResponse<TItem> => ({
    data: items,
    meta: {
        current_page: page,
        last_page: 5,
        per_page: 10,
        total: 50,
    },
});

afterEach(() => {
    vi.useRealTimers();
});

describe("useServerPaginatedList", () => {
    it("loads page payload and updates pagination state", async () => {
        const fetchPage = vi.fn(async (params: { page: number }) =>
            buildResponse([params.page], params.page),
        );

        const scope = effectScope();
        const list = scope.run(() =>
            useServerPaginatedList<number, { page: number }>({
                buildParams: (page) => ({ page }),
                fetchPage,
            }),
        );

        expect(list).not.toBeNull();
        if (!list) {
            scope.stop();
            return;
        }

        await list.load(3);

        expect(fetchPage).toHaveBeenCalledWith({ page: 3 });
        expect(list.items.value).toEqual([3]);
        expect(list.page.value).toBe(3);
        expect(list.meta.current_page).toBe(3);
        expect(list.meta.last_page).toBe(5);

        scope.stop();
    });

    it("debounces filter reload and requests first page with latest filter value", async () => {
        vi.useFakeTimers();

        const filters = reactive({ q: "" });
        const fetchPage = vi.fn(async (params: { page: number; q: string }) =>
            buildResponse([params.q], params.page),
        );

        const scope = effectScope();
        const list = scope.run(() =>
            useServerPaginatedList<string, { page: number; q: string }>({
                buildParams: (page) => ({ page, q: filters.q }),
                fetchPage,
                filterSource: () => filters.q,
                debounceMs: 120,
            }),
        );

        expect(list).not.toBeNull();
        if (!list) {
            scope.stop();
            return;
        }

        filters.q = "a";
        await nextTick();
        filters.q = "ab";
        await nextTick();

        expect(fetchPage).not.toHaveBeenCalled();

        await vi.advanceTimersByTimeAsync(120);

        expect(fetchPage).toHaveBeenCalledTimes(1);
        expect(fetchPage).toHaveBeenCalledWith({ page: 1, q: "ab" });
        expect(list.items.value).toEqual(["ab"]);

        scope.stop();
    });

    it("resets list state on error when resetOnError is enabled", async () => {
        const onError = vi.fn();
        const fetchPage = vi.fn(async () => {
            throw new Error("Failed");
        });

        const scope = effectScope();
        const list = scope.run(() =>
            useServerPaginatedList<number, { page: number }>({
                buildParams: (page) => ({ page }),
                fetchPage,
                resetOnError: true,
                onError,
            }),
        );

        expect(list).not.toBeNull();
        if (!list) {
            scope.stop();
            return;
        }

        list.items.value = [1, 2, 3];
        list.page.value = 4;
        list.meta.current_page = 4;
        list.meta.last_page = 7;
        list.meta.per_page = 25;
        list.meta.total = 100;

        await list.load(4);

        expect(onError).toHaveBeenCalledTimes(1);
        expect(list.items.value).toEqual([]);
        expect(list.page.value).toBe(1);
        expect(list.meta.current_page).toBe(1);
        expect(list.meta.last_page).toBe(1);
        expect(list.meta.total).toBe(0);

        scope.stop();
    });
});
