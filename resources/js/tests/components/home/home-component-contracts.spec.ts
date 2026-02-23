/* @vitest-environment jsdom */

import { RouterLinkStub, mount } from "@vue/test-utils";
import { describe, expect, it } from "vitest";

import HomeHeroSection from "@/components/home/HomeHeroSection.vue";
import HomeKpiGrid from "@/components/home/HomeKpiGrid.vue";

describe("home hero section contract", () => {
    it("renders admin action when admin access is available", () => {
        const wrapper = mount(HomeHeroSection, {
            props: {
                canAccessAdmin: true,
                canAccessAccount: false,
            },
            global: {
                stubs: {
                    RouterLink: RouterLinkStub,
                },
            },
        });

        const links = wrapper.findAllComponents(RouterLinkStub).map((link) => link.props("to"));
        expect(links).toContain("/catalog");
        expect(links).toContain("/admin");
        expect(wrapper.text()).not.toContain("Open account");
    });

    it("renders sign in action for guest users", () => {
        const wrapper = mount(HomeHeroSection, {
            props: {
                canAccessAdmin: false,
                canAccessAccount: false,
            },
            global: {
                stubs: {
                    RouterLink: RouterLinkStub,
                },
            },
        });

        const links = wrapper.findAllComponents(RouterLinkStub).map((link) => link.props("to"));
        expect(links).toContain("/auth");
    });
});

describe("home kpi grid contract", () => {
    it("renders all storefront highlights", () => {
        const wrapper = mount(HomeKpiGrid);

        expect(wrapper.text()).toContain("Fast UI shell");
        expect(wrapper.text()).toContain("Catalog first");
        expect(wrapper.text()).toContain("Admin ready");
    });
});
