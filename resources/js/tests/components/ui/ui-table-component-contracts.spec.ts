/* @vitest-environment jsdom */

import { describe, expect, it } from "vitest";

import AppTableActionsCell from "@/components/ui/table/AppTableActionsCell.vue";
import AppTableEmptyStateRow from "@/components/ui/table/AppTableEmptyStateRow.vue";
import AppTableSection from "@/components/ui/table/AppTableSection.vue";
import { mount } from "./helpers/ui-test-helpers";

describe("app table section contract", () => {
    it("renders table wrapper and optional top spacing class", () => {
        const wrapper = mount(AppTableSection, {
            props: {
                withTopSpacing: true,
            },
            slots: {
                default: "<tbody><tr><td>Row</td></tr></tbody>",
            },
        });

        expect(wrapper.classes()).toContain("table-wrap");
        expect(wrapper.classes()).toContain("actions--top");
        expect(wrapper.find("table.table").exists()).toBe(true);
        expect(wrapper.text()).toContain("Row");
    });
});

describe("app table actions cell contract", () => {
    it("renders td wrapper with actions layout and attrs passthrough", () => {
        const wrapper = mount(AppTableActionsCell, {
            attrs: {
                class: "actions-column",
                "data-testid": "table-actions-cell",
            },
            slots: {
                default: "<button>Edit</button><button>Delete</button>",
            },
        });

        expect(wrapper.element.tagName).toBe("TD");
        expect(wrapper.classes()).toContain("actions-column");
        expect(wrapper.attributes("data-testid")).toBe("table-actions-cell");
        expect(wrapper.find(".actions").exists()).toBe(true);
        expect(wrapper.text()).toContain("Edit");
        expect(wrapper.text()).toContain("Delete");
    });

    it("supports top spacing layout mode", () => {
        const wrapper = mount(AppTableActionsCell, {
            props: {
                withTopSpacing: true,
            },
            slots: {
                default: "<button>Action</button>",
            },
        });

        expect(wrapper.find(".actions").classes()).toContain("actions--top");
    });
});

describe("app table empty state row contract", () => {
    it("renders row/colspan and delegated empty-state message", () => {
        const wrapper = mount(AppTableEmptyStateRow, {
            props: {
                colspan: 6,
                message: "No records found",
            },
        });

        expect(wrapper.element.tagName).toBe("TR");
        expect(wrapper.get("td").attributes("colspan")).toBe("6");
        expect(wrapper.text()).toContain("No records found");
    });
});
