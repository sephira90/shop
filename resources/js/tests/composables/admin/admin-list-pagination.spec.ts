import { describe, expect, it } from "vitest";

import { resolvePageAfterLastItemRemoval } from "@/composables/admin/adminListPagination";

describe("resolvePageAfterLastItemRemoval", () => {
    it("keeps first page when removing the only visible item", () => {
        expect(
            resolvePageAfterLastItemRemoval({
                currentPage: 1,
                visibleItemsCount: 1,
            }),
        ).toBe(1);
    });

    it("falls back to previous page when removing the only visible item on non-first page", () => {
        expect(
            resolvePageAfterLastItemRemoval({
                currentPage: 4,
                visibleItemsCount: 1,
            }),
        ).toBe(3);
    });

    it("keeps current page when more than one item is visible", () => {
        expect(
            resolvePageAfterLastItemRemoval({
                currentPage: 3,
                visibleItemsCount: 8,
            }),
        ).toBe(3);
    });
});
