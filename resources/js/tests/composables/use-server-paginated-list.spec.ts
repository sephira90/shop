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

    it("keeps newest page data when older request resolves later", async () => {
        const requests: Array<{
            page: number;
            deferred: ReturnType<typeof createDeferred<ListResponse<number>>>;
        }> = [];
        const fetchPage = vi.fn((params: { page: number }) => {
            const deferred = createDeferred<ListResponse<number>>();
            requests.push({
                page: params.page,
                deferred,
            });

            return deferred.promise;
        });

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

        const firstLoad = list.load(1);
        const secondLoad = list.load(2);

        expect(requests).toHaveLength(2);

        requests[1]?.deferred.resolve(buildResponse([2], 2));
        await secondLoad;
        expect(list.items.value).toEqual([2]);
        expect(list.page.value).toBe(2);

        requests[0]?.deferred.resolve(buildResponse([1], 1));
        await firstLoad;
        expect(list.items.value).toEqual([2]);
        expect(list.page.value).toBe(2);

        scope.stop();
    });

    it("aborts previous request and does not emit errors for cancellation", async () => {
        const onError = vi.fn();
        const resolvers: Array<(response: ListResponse<number>) => void> = [];
        const fetchPage = vi.fn(
            (
                _params: { page: number },
                context?: {
                    signal?: AbortSignal;
                },
            ) =>
                new Promise<ListResponse<number>>((resolve, reject) => {
                    const abort = () => {
                        reject(new DOMException("Aborted", "AbortError"));
                    };

                    context?.signal?.addEventListener("abort", abort, { once: true });
                    resolvers.push((response) => {
                        context?.signal?.removeEventListener("abort", abort);
                        resolve(response);
                    });
                }),
        );

        const scope = effectScope();
        const list = scope.run(() =>
            useServerPaginatedList<number, { page: number }>({
                buildParams: (page) => ({ page }),
                fetchPage,
                onError,
                resetOnError: true,
            }),
        );

        expect(list).not.toBeNull();
        if (!list) {
            scope.stop();
            return;
        }

        const firstLoad = list.load(1);
        const secondLoad = list.load(2);

        expect(fetchPage).toHaveBeenCalledTimes(2);

        resolvers[1]?.(buildResponse([2], 2));
        await secondLoad;
        await firstLoad;

        expect(onError).not.toHaveBeenCalled();
        expect(list.items.value).toEqual([2]);
        expect(list.page.value).toBe(2);

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
