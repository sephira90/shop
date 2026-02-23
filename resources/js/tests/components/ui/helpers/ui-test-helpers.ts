import { RouterLinkStub, mount } from "@vue/test-utils";
import type { PaginationMeta } from "@/types/pagination";

export { RouterLinkStub, mount };

export const defaultPaginationMeta: PaginationMeta = {
    current_page: 2,
    last_page: 5,
    total: 120,
    per_page: 30,
};
