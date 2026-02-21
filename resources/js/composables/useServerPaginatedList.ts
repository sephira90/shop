import { ref, type WatchSource } from "vue";

import type { ListResponse } from "@/api/response";
import { usePaginationMeta } from "@/composables/usePaginationMeta";
import { useServerListFilters } from "@/composables/useServerListFilters";

interface UseServerPaginatedListOptions<TItem, TParams> {
    buildParams: (page: number) => TParams;
    fetchPage: (params: TParams) => Promise<ListResponse<TItem>>;
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

    const load = async (targetPage = page.value): Promise<void> => {
        options.onLoading?.();
        isLoading.value = true;

        try {
            const response = await options.fetchPage(options.buildParams(targetPage));
            items.value = response.data;
            applyMeta(response.meta);
            page.value = response.meta.current_page;

            if (options.onLoaded) {
                await options.onLoaded(response);
            }
        } catch (error: unknown) {
            if (options.resetOnError === true) {
                items.value = [];
                resetMeta();
                page.value = 1;
            }

            options.onError?.(error);
        } finally {
            isLoading.value = false;
        }
    };

    if (options.filterSource !== undefined) {
        useServerListFilters(options.filterSource, () => load(1), {
            debounceMs: options.debounceMs,
        });
    }

    return {
        items,
        page,
        isLoading,
        meta,
        load,
    };
};
