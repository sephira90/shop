/* @vitest-environment jsdom */

import { describe, expect, it } from "vitest";

import AppCard from "@/components/ui/layout/AppCard.vue";
import AppGridThreeColumns from "@/components/ui/layout/AppGridThreeColumns.vue";
import AppGridTwoColumns from "@/components/ui/layout/AppGridTwoColumns.vue";
import { mount } from "./helpers/ui-test-helpers";

describe("app card contract", () => {
    it("renders card class and custom tag", () => {
        const wrapper = mount(AppCard, {
            props: {
                tag: "section",
            },
            slots: {
                default: "<p>Content</p>",
            },
        });

        expect(wrapper.element.tagName).toBe("SECTION");
        expect(wrapper.classes()).toContain("card");
        expect(wrapper.text()).toContain("Content");
    });
});

describe("app grid two columns contract", () => {
    it("renders grid-2 wrapper with custom tag", () => {
        const wrapper = mount(AppGridTwoColumns, {
            props: {
                tag: "section",
                withTopSpacing: true,
            },
            slots: {
                default: "<p>Column A</p><p>Column B</p>",
            },
        });

        expect(wrapper.element.tagName).toBe("SECTION");
        expect(wrapper.classes()).toContain("grid");
        expect(wrapper.classes()).toContain("grid-2");
        expect(wrapper.classes()).toContain("actions--top");
        expect(wrapper.text()).toContain("Column A");
        expect(wrapper.text()).toContain("Column B");
    });
});

describe("app grid three columns contract", () => {
    it("renders grid-3 wrapper with custom tag", () => {
        const wrapper = mount(AppGridThreeColumns, {
            props: {
                tag: "section",
                withTopSpacing: true,
            },
            slots: {
                default: "<p>Column A</p><p>Column B</p><p>Column C</p>",
            },
        });

        expect(wrapper.element.tagName).toBe("SECTION");
        expect(wrapper.classes()).toContain("grid");
        expect(wrapper.classes()).toContain("grid-3");
        expect(wrapper.classes()).toContain("actions--top");
        expect(wrapper.text()).toContain("Column A");
        expect(wrapper.text()).toContain("Column B");
        expect(wrapper.text()).toContain("Column C");
    });
});
