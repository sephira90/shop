import { describe, expect, it } from "vitest";

import {
    buildCategoryMutationPayload,
    createCategoryFormState,
} from "@/validators/admin/categories";

describe("category validator", () => {
    it("creates default form state", () => {
        expect(createCategoryFormState()).toEqual({
            parent_id: "",
            name: "",
            slug: "",
            description: "",
            meta_title: "",
            meta_description: "",
            is_active: true,
            sort_order: "0",
        });
    });

    it("builds normalized payload", () => {
        const payload = buildCategoryMutationPayload({
            parent_id: "12",
            name: "  Shoes  ",
            slug: "  shoes  ",
            description: "  Winter collection  ",
            meta_title: "  Shoes page  ",
            meta_description: "  Meta text  ",
            is_active: false,
            sort_order: "18",
        });

        expect(payload).toEqual({
            parent_id: 12,
            name: "Shoes",
            slug: "shoes",
            description: "Winter collection",
            meta_title: "Shoes page",
            meta_description: "Meta text",
            is_active: false,
            sort_order: 18,
        });
    });
});
