import { onScopeDispose, ref, type WatchSource } from "vue";

import type { ListResponse } from "@/api/response";
import { isAbortLikeError } from "@/composables/requestError";
import { usePaginationMeta } from "@/composables/usePaginationMeta";
import { useServerListFilters } from "@/composables/useServerListFilters";

interface UseServerPaginatedListOptions<TItem, TParams> {
    buildParams: (page: number) => TParams;
    fetchPage: (
        params: TParams,
        context?: {
            signal?: AbortSignal;
        },
    ) => Promise<ListResponse<TItem>>;
    filterSource?: WatchSource<unknown> | WatchSource<unknown>[];
    debounceMs?: number;
    resetOnError?: boolean;
    initialPage?: number;
    onLoading?: () => void;
    onLoaded?: (response: ListResponse<TItem>) => void | Promise<void>;
    onError?: (error: unknown) => void;
}

export const useServerPaginatedList = <TItem, TParams>(
    options: UseServerPaginatedListOptions<TItem, TParams>,
) => {
    const items = ref<TItem[]>([]);
    const page = ref(Math.max(1, options.initialPage ?? 1));
    const isLoading = ref(false);
    const { meta, applyMeta, resetMeta } = usePaginationMeta();
    let activeRequestId = 0;
    let activeAbortController: AbortController | null = null;

    const load = async (targetPage = page.value): Promise<void> => {
        const requestId = ++activeRequestId;
        activeAbortController?.abort();
        const abortController =
            typeof AbortController === "undefined" ? null : new AbortController();
        activeAbortController = abortController;

        options.onLoading?.();
        isLoading.value = true;

        try {
            const params = options.buildParams(targetPage);
            const response =
                options.fetchPage.length >= 2
                    ? await options.fetchPage(params, {
                          signal: abortController?.signal,
                      })
                    : await options.fetchPage(params);

            if (requestId !== activeRequestId) {
                return;
            }

            items.value = response.data;
            applyMeta(response.meta);
            page.value = response.meta.current_page;

            if (options.onLoaded) {
                await options.onLoaded(response);
            }
        } catch (error: unknown) {
            if (requestId !== activeRequestId || isAbortLikeError(error)) {
                return;
            }

            if (options.resetOnError === true) {
                items.value = [];
                resetMeta();
                page.value = 1;
            }

            options.onError?.(error);
        } finally {
            if (requestId === activeRequestId) {
                isLoading.value = false;
                activeAbortController = null;
            }
        }
    };

    if (options.filterSource !== undefined) {
        useServerListFilters(options.filterSource, () => load(1), {
            debounceMs: options.debounceMs,
        });
    }

    onScopeDispose(() => {
        activeRequestId += 1;
        activeAbortController?.abort();
        activeAbortController = null;
    });

    return {
        items,
        page,
        isLoading,
        meta,
        load,
    };
};
