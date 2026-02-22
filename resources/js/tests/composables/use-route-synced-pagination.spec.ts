import { describe, expect, it, vi } from "vitest";
import { effectScope, reactive, ref } from "vue";
import type { LocationQueryRaw } from "vue-router";

import { useRouteSyncedPagination } from "@/composables/useRouteSyncedPagination";

interface DemoFilters {
    search: string;
    page: number;
}

const parseQuery = (query: Readonly<Record<string, unknown>>): DemoFilters => ({
    search: String(query.q ?? "").trim(),
    page: Number(query.page ?? 1) || 1,
});

const buildQuery = (filters: DemoFilters): LocationQueryRaw => {
    const query: LocationQueryRaw = {};

    if (filters.search !== "") {
        query.q = filters.search;
    }

    if (filters.page > 1) {
        query.page = String(filters.page);
    }

    return query;
};

describe("useRouteSyncedPagination", () => {
    it("updates route and reloads via route watcher when query changes", async () => {
        const route = reactive<{ query: Record<string, unknown> }>({
            query: {},
        });
        const replace = vi.fn(async (to: unknown) => {
            const query = (to as { query?: Record<string, unknown> }).query ?? {};
            route.query = query;
        });
        const fetchPage = vi.fn(async (targetPage: number) => {
            void targetPage;
        });
        const search = ref("vip");
        const page = ref(1);

        const scope = effectScope();
        const api = scope.run(() =>
            useRouteSyncedPagination<DemoFilters>({
                route,
                router: { replace },
                parseRouteQuery: parseQuery,
                buildRouteQuery: buildQuery,
                isSameRouteQuery: (left, right) =>
                    parseQuery(left).search === parseQuery(right).search &&
                    parseQuery(left).page === parseQuery(right).page,
                applyParsedFilters: (parsed) => {
                    search.value = parsed.search;
                    page.value = parsed.page;
                },
                readFiltersForPage: (targetPage) => ({
                    search: search.value.trim(),
                    page: targetPage,
                }),
                fetchPage: async (targetPage) => fetchPage(targetPage),
                immediate: false,
            }),
        );

        expect(api).not.toBeNull();
        if (!api) {
            scope.stop();
            return;
        }

        await api.load(2);

        expect(replace).toHaveBeenCalledWith({
            query: {
                q: "vip",
                page: "2",
            },
        });
        await vi.waitFor(() => {
            expect(fetchPage).toHaveBeenCalledWith(2);
        });

        scope.stop();
    });

    it("calls fetch directly when route query is already synced", async () => {
        const route = reactive<{ query: Record<string, unknown> }>({
            query: {
                q: "vip",
                page: "2",
            },
        });
        const replace = vi.fn(async (to: unknown) => {
            const query = (to as { query?: Record<string, unknown> }).query ?? {};
            route.query = query;
        });
        const fetchPage = vi.fn(async (targetPage: number) => {
            void targetPage;
        });
        const search = ref("vip");

        const scope = effectScope();
        const api = scope.run(() =>
            useRouteSyncedPagination<DemoFilters>({
                route,
                router: { replace },
                parseRouteQuery: parseQuery,
                buildRouteQuery: buildQuery,
                isSameRouteQuery: (left, right) =>
                    parseQuery(left).search === parseQuery(right).search &&
                    parseQuery(left).page === parseQuery(right).page,
                applyParsedFilters: () => {},
                readFiltersForPage: (targetPage) => ({
                    search: search.value.trim(),
                    page: targetPage,
                }),
                fetchPage: async (targetPage) => fetchPage(targetPage),
                immediate: false,
            }),
        );

        expect(api).not.toBeNull();
        if (!api) {
            scope.stop();
            return;
        }

        await api.load(2);

        expect(replace).not.toHaveBeenCalled();
        expect(fetchPage).toHaveBeenCalledWith(2);

        scope.stop();
    });

    it("works without route sync context", async () => {
        const fetchPage = vi.fn(async (targetPage: number) => {
            void targetPage;
        });

        const scope = effectScope();
        const api = scope.run(() =>
            useRouteSyncedPagination<DemoFilters>({
                parseRouteQuery: parseQuery,
                buildRouteQuery: buildQuery,
                isSameRouteQuery: () => false,
                applyParsedFilters: () => {},
                readFiltersForPage: (targetPage) => ({
                    search: "vip",
                    page: targetPage,
                }),
                fetchPage: async (targetPage) => fetchPage(targetPage),
                immediate: false,
            }),
        );

        expect(api).not.toBeNull();
        if (!api) {
            scope.stop();
            return;
        }

        await api.load(4);
        expect(fetchPage).toHaveBeenCalledWith(4);
        expect(api.hasRouteSync).toBe(false);

        scope.stop();
    });
});
