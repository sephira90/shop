import { reactive } from "vue";

import type { PaginationMeta } from "@/types/pagination";

const createDefaultMeta = (): PaginationMeta => ({
    current_page: 1,
    last_page: 1,
    per_page: 1,
    total: 0,
});

export const usePaginationMeta = () => {
    const meta = reactive<PaginationMeta>(createDefaultMeta());

    const applyMeta = (nextMeta: PaginationMeta): void => {
        meta.current_page = nextMeta.current_page;
        meta.last_page = nextMeta.last_page;
        meta.per_page = nextMeta.per_page ?? meta.per_page ?? 1;
        meta.total = nextMeta.total;
    };

    const resetMeta = (): void => {
        const defaults = createDefaultMeta();
        applyMeta(defaults);
    };

    return {
        meta,
        applyMeta,
        resetMeta,
    };
};
