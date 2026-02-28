import { afterEach, describe, expect, it, vi } from "vitest";
import { effectScope, nextTick, reactive, ref } from "vue";
import type { LocationQueryRaw } from "vue-router";

import { useAdminRouteSyncedLoader } from "@/composables/admin/adminRouteSync";

interface DemoFilters {
    searchQuery: string;
    page: number;
}

const parseRouteQuery = (query: Readonly<Record<string, unknown>>): DemoFilters => ({
    searchQuery: String(query.q ?? "").trim(),
    page: Number(query.page ?? 1) || 1,
});

const buildRouteQuery = (filters: DemoFilters): LocationQueryRaw => {
    const query: LocationQueryRaw = {};

    if (filters.searchQuery !== "") {
        query.q = filters.searchQuery;
    }

    if (filters.page > 1) {
        query.page = String(filters.page);
    }

    return query;
};

afterEach(() => {
    vi.useRealTimers();
});

describe("admin route sync loader", () => {
    it("loads directly when route sync is not configured", async () => {
        const page = ref(3);
        const searchQuery = ref("");
        const fetchPage = vi.fn(async (targetPage: number) => {
            void targetPage;
        });

        const scope = effectScope();
        const api = scope.run(() =>
            useAdminRouteSyncedLoader({
                page,
                fetchPage,
                parseRouteQuery,
                buildRouteQuery,
                isSameRouteQuery: (left, right) =>
                    parseRouteQuery(left).searchQuery === parseRouteQuery(right).searchQuery &&
                    parseRouteQuery(left).page === parseRouteQuery(right).page,
                applyParsedFilters: (parsed) => {
                    searchQuery.value = parsed.searchQuery;
                    page.value = parsed.page;
                },
                readFiltersForPage: (targetPage) => ({
                    searchQuery: searchQuery.value.trim(),
                    page: targetPage,
                }),
                filterSource: searchQuery,
            }),
        );

        expect(api).not.toBeNull();
        if (!api) {
            scope.stop();
            return;
        }

        await api.load();
        await api.load(5);

        expect(fetchPage).toHaveBeenNthCalledWith(1, 3);
        expect(fetchPage).toHaveBeenNthCalledWith(2, 5);

        scope.stop();
    });

    it("syncs route and reloads first page when watched filters change", async () => {
        vi.useFakeTimers();

        const route = reactive<{ query: Record<string, unknown> }>({
            query: {},
        });
        const replace = vi.fn(async (to: unknown) => {
            const query = (to as { query?: Record<string, unknown> }).query ?? {};
            route.query = query;
        });
        const page = ref(1);
        const searchQuery = ref("");
        const fetchPage = vi.fn(async (targetPage: number) => {
            void targetPage;
        });

        const scope = effectScope();
        const api = scope.run(() =>
            useAdminRouteSyncedLoader({
                routeSync: {
                    route,
                    router: { replace },
                },
                page,
                fetchPage,
                parseRouteQuery,
                buildRouteQuery,
                isSameRouteQuery: (left, right) =>
                    parseRouteQuery(left).searchQuery === parseRouteQuery(right).searchQuery &&
                    parseRouteQuery(left).page === parseRouteQuery(right).page,
                applyParsedFilters: (parsed) => {
                    searchQuery.value = parsed.searchQuery;
                    page.value = parsed.page;
                },
                readFiltersForPage: (targetPage) => ({
                    searchQuery: searchQuery.value.trim(),
                    page: targetPage,
                }),
                filterSource: searchQuery,
                debounceMs: 120,
            }),
        );

        expect(api).not.toBeNull();
        if (!api) {
            scope.stop();
            return;
        }

        searchQuery.value = "  vip  ";
        await nextTick();
        await vi.advanceTimersByTimeAsync(120);

        expect(replace).toHaveBeenCalledWith({
            query: {
                q: "vip",
            },
        });
        await vi.waitFor(() => {
            expect(fetchPage).toHaveBeenCalledWith(1);
        });
        await vi.advanceTimersByTimeAsync(120);
        expect(fetchPage).toHaveBeenCalledTimes(1);
        expect(replace).toHaveBeenCalledTimes(1);

        scope.stop();
    });

    it("does not schedule duplicate reload after external route filter update", async () => {
        vi.useFakeTimers();

        const route = reactive<{ query: Record<string, unknown> }>({
            query: {},
        });
        const replace = vi.fn(async (to: unknown) => {
            const query = (to as { query?: Record<string, unknown> }).query ?? {};
            route.query = query;
        });
        const page = ref(1);
        const searchQuery = ref("");
        const fetchPage = vi.fn(async (targetPage: number) => {
            void targetPage;
        });

        const scope = effectScope();
        const api = scope.run(() =>
            useAdminRouteSyncedLoader({
                routeSync: {
                    route,
                    router: { replace },
                },
                page,
                fetchPage,
                parseRouteQuery,
                buildRouteQuery,
                isSameRouteQuery: (left, right) =>
                    parseRouteQuery(left).searchQuery === parseRouteQuery(right).searchQuery &&
                    parseRouteQuery(left).page === parseRouteQuery(right).page,
                applyParsedFilters: (parsed) => {
                    searchQuery.value = parsed.searchQuery;
                    page.value = parsed.page;
                },
                readFiltersForPage: (targetPage) => ({
                    searchQuery: searchQuery.value.trim(),
                    page: targetPage,
                }),
                filterSource: searchQuery,
                debounceMs: 120,
            }),
        );

        expect(api).not.toBeNull();
        if (!api) {
            scope.stop();
            return;
        }

        fetchPage.mockClear();
        route.query = {
            q: "winter",
            page: "2",
        };
        await nextTick();

        await vi.waitFor(() => {
            expect(fetchPage).toHaveBeenCalledWith(2);
        });
        await vi.advanceTimersByTimeAsync(120);

        expect(fetchPage).toHaveBeenCalledTimes(1);
        expect(replace).not.toHaveBeenCalled();

        scope.stop();
    });
});
