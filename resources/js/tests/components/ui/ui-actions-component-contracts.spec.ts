/* @vitest-environment jsdom */

import { describe, expect, it } from "vitest";

import AppActionsRow from "@/components/ui/actions/AppActionsRow.vue";
import AppButton from "@/components/ui/actions/AppButton.vue";
import AppStackBetween from "@/components/ui/actions/AppStackBetween.vue";
import AppSubmitResetActions from "@/components/ui/actions/AppSubmitResetActions.vue";
import { RouterLinkStub, mount } from "./helpers/ui-test-helpers";

describe("app button contract", () => {
    it("renders button classes for variants and supports custom tag", () => {
        const primaryWrapper = mount(AppButton, {
            props: {
                variant: "primary",
                type: "submit",
            },
            slots: {
                default: "Save",
            },
        });

        const mutedLinkWrapper = mount(AppButton, {
            props: {
                as: "a",
                variant: "muted",
            },
            attrs: {
                href: "/catalog",
            },
            slots: {
                default: "Catalog",
            },
        });

        expect(primaryWrapper.element.tagName).toBe("BUTTON");
        expect(primaryWrapper.classes()).toContain("btn");
        expect(primaryWrapper.classes()).toContain("btn-primary");
        expect(primaryWrapper.attributes("type")).toBe("submit");
        expect(primaryWrapper.text()).toContain("Save");

        expect(mutedLinkWrapper.element.tagName).toBe("A");
        expect(mutedLinkWrapper.classes()).toContain("btn");
        expect(mutedLinkWrapper.classes()).toContain("btn-muted");
        expect(mutedLinkWrapper.attributes("type")).toBeUndefined();
        expect(mutedLinkWrapper.attributes("href")).toBe("/catalog");
    });

    it("adds secure rel for external links opened in new tab", () => {
        const wrapper = mount(AppButton, {
            props: {
                as: "a",
                href: "https://example.com",
                target: "_blank",
            },
            slots: {
                default: "Open",
            },
        });

        expect(wrapper.attributes("rel")).toBe("noopener noreferrer");
    });

    it("prevents disabled links from navigating", async () => {
        const wrapper = mount(AppButton, {
            props: {
                as: "a",
                href: "https://example.com",
                disabled: true,
            },
            slots: {
                default: "Disabled",
            },
        });

        expect(wrapper.attributes("href")).toBeUndefined();
        expect(wrapper.attributes("aria-disabled")).toBe("true");
        expect(wrapper.attributes("tabindex")).toBe("-1");

        await wrapper.trigger("click");
    });

    it("infers router-link when 'to' is provided", () => {
        const wrapper = mount(AppButton, {
            props: {
                to: "/catalog",
            },
            global: {
                stubs: {
                    RouterLink: RouterLinkStub,
                },
            },
            slots: {
                default: "Catalog",
            },
        });

        const routerLink = wrapper.findComponent(RouterLinkStub);
        expect(routerLink.exists()).toBe(true);
        expect(routerLink.props("to")).toBe("/catalog");
    });
});

describe("app actions row contract", () => {
    it("renders actions wrapper and optional top spacing class", () => {
        const wrapper = mount(AppActionsRow, {
            props: {
                withTopSpacing: true,
            },
            slots: {
                default: "<button>Action</button>",
            },
        });

        expect(wrapper.classes()).toContain("actions");
        expect(wrapper.classes()).toContain("actions--top");
        expect(wrapper.text()).toContain("Action");
    });

    it("passes attrs to root and keeps default layout without top spacing", () => {
        const wrapper = mount(AppActionsRow, {
            attrs: {
                id: "actions-row",
                "data-testid": "actions-row",
                class: "actions-extra",
            },
            slots: {
                default: "<button>Primary</button><button>Secondary</button>",
            },
        });

        expect(wrapper.classes()).toContain("actions");
        expect(wrapper.classes()).not.toContain("actions--top");
        expect(wrapper.classes()).toContain("actions-extra");
        expect(wrapper.attributes("id")).toBe("actions-row");
        expect(wrapper.attributes("data-testid")).toBe("actions-row");
        expect(wrapper.text()).toContain("Primary");
        expect(wrapper.text()).toContain("Secondary");
    });
});

describe("app stack between contract", () => {
    it("renders stack-between wrapper and optional top spacing class", () => {
        const wrapper = mount(AppStackBetween, {
            props: {
                withTopSpacing: true,
            },
            slots: {
                default: "<p>Left</p><p>Right</p>",
            },
        });

        expect(wrapper.classes()).toContain("stack");
        expect(wrapper.classes()).toContain("stack--between");
        expect(wrapper.classes()).toContain("actions--top");
        expect(wrapper.text()).toContain("Left");
        expect(wrapper.text()).toContain("Right");
    });
});

describe("app submit reset actions contract", () => {
    it("renders action slots in actions wrapper", () => {
        const wrapper = mount(AppSubmitResetActions, {
            slots: {
                primary: "<button type='submit'>Save</button>",
                secondary: "<button type='button'>Cancel</button>",
            },
        });

        const actions = wrapper.find(".actions");
        expect(actions.exists()).toBe(true);
        expect(wrapper.text()).toContain("Save");
        expect(wrapper.text()).toContain("Cancel");
    });

    it("supports top spacing and attrs passthrough to actions root", () => {
        const wrapper = mount(AppSubmitResetActions, {
            props: {
                withTopSpacing: true,
            },
            attrs: {
                id: "submit-reset-actions",
                "data-testid": "submit-reset-actions",
                class: "actions-extra",
            },
            slots: {
                primary: "<button type='submit'>Save</button>",
            },
        });

        const actions = wrapper.find(".actions");
        expect(actions.exists()).toBe(true);
        expect(actions.classes()).toContain("actions--top");
        expect(actions.classes()).toContain("actions-extra");
        expect(actions.attributes("id")).toBe("submit-reset-actions");
        expect(actions.attributes("data-testid")).toBe("submit-reset-actions");
        expect(wrapper.text()).toContain("Save");
    });
});
