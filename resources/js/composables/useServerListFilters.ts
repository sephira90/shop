import { onScopeDispose, watch, type WatchSource } from "vue";

interface UseServerListFiltersOptions {
    debounceMs?: number;
    shouldReload?: () => boolean;
}

type ReloadHandler = () => void | Promise<void>;

export const useServerListFilters = (
    source: WatchSource<unknown> | WatchSource<unknown>[],
    reload: ReloadHandler,
    options: UseServerListFiltersOptions = {},
): void => {
    const debounceMs = Math.max(0, options.debounceMs ?? 300);
    let timer: ReturnType<typeof setTimeout> | null = null;

    const stop = watch(source, () => {
        if (options.shouldReload && !options.shouldReload()) {
            return;
        }

        if (timer !== null) {
            clearTimeout(timer);
        }

        timer = setTimeout(() => {
            void reload();
        }, debounceMs);
    });

    onScopeDispose(() => {
        stop();

        if (timer !== null) {
            clearTimeout(timer);
            timer = null;
        }
    });
};
