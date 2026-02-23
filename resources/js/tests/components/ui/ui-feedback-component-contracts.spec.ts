/* @vitest-environment jsdom */

import { describe, expect, it } from "vitest";

import AppEmptyState from "@/components/ui/feedback/AppEmptyState.vue";
import AppNotice from "@/components/ui/feedback/AppNotice.vue";
import { mount } from "./helpers/ui-test-helpers";

describe("app notice contract", () => {
    it("renders success and error variants", () => {
        const successWrapper = mount(AppNotice, {
            props: {
                message: "Saved successfully",
                variant: "success",
            },
        });
        const errorWrapper = mount(AppNotice, {
            props: {
                message: "Failed to save",
                variant: "error",
            },
        });

        expect(successWrapper.classes()).toContain("notice--success");
        expect(errorWrapper.classes()).toContain("notice--error");
    });
});

describe("app empty state contract", () => {
    it("supports card wrapper and custom tag", () => {
        const wrapper = mount(AppEmptyState, {
            props: {
                message: "Nothing to show",
                inCard: true,
                tag: "section",
            },
        });

        expect(wrapper.element.tagName).toBe("SECTION");
        expect(wrapper.classes()).toContain("card");
        expect(wrapper.classes()).toContain("empty-state");
        expect(wrapper.text()).toContain("Nothing to show");
    });
});
