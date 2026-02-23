/* @vitest-environment jsdom */

import { describe, expect, it } from "vitest";

import AppMutedText from "@/components/ui/typography/AppMutedText.vue";
import AppSectionTitle from "@/components/ui/typography/AppSectionTitle.vue";
import { mount } from "./helpers/ui-test-helpers";

describe("app section title contract", () => {
    it("renders section title class with custom tag", () => {
        const wrapper = mount(AppSectionTitle, {
            props: {
                tag: "h1",
            },
            slots: {
                default: "Orders",
            },
        });

        expect(wrapper.element.tagName).toBe("H1");
        expect(wrapper.classes()).toContain("section-title");
        expect(wrapper.text()).toContain("Orders");
    });
});

describe("app muted text contract", () => {
    it("renders muted class and supports inline tag", () => {
        const wrapper = mount(AppMutedText, {
            props: {
                tag: "span",
            },
            slots: {
                default: "Total",
            },
        });

        expect(wrapper.element.tagName).toBe("SPAN");
        expect(wrapper.classes()).toContain("muted");
        expect(wrapper.text()).toContain("Total");
    });
});
