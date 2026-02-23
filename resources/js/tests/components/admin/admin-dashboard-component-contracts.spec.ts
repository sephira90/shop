/* @vitest-environment jsdom */

import { RouterLinkStub, mount } from "@vue/test-utils";
import { describe, expect, it } from "vitest";

import AdminDashboardHero from "@/components/admin/dashboard/AdminDashboardHero.vue";
import AdminDashboardNavCard from "@/components/admin/dashboard/AdminDashboardNavCard.vue";
import AdminDashboardNavGrid from "@/components/admin/dashboard/AdminDashboardNavGrid.vue";

describe("admin dashboard hero contract", () => {
    it("renders heading content", () => {
        const wrapper = mount(AdminDashboardHero);

        expect(wrapper.text()).toContain("Admin dashboard");
        expect(wrapper.text()).toContain("Manage products, orders and promotions");
    });
});

describe("admin dashboard nav card contract", () => {
    it("renders route card with icon and copy", () => {
        const wrapper = mount(AdminDashboardNavCard, {
            props: {
                to: "/admin/orders",
                title: "Orders",
                description: "Track order statuses and totals.",
                iconPath: "M6 4h9",
            },
            global: {
                stubs: {
                    RouterLink: RouterLinkStub,
                },
            },
        });

        const link = wrapper.getComponent(RouterLinkStub);
        expect(link.props("to")).toBe("/admin/orders");
        expect(wrapper.text()).toContain("Orders");
        expect(wrapper.text()).toContain("Track order statuses and totals.");
        expect(wrapper.find("path").attributes("d")).toBe("M6 4h9");
    });
});

describe("admin dashboard nav grid contract", () => {
    it("renders all navigation cards from items", () => {
        const wrapper = mount(AdminDashboardNavGrid, {
            props: {
                items: [
                    {
                        to: "/admin/categories",
                        title: "Categories",
                        description: "Organize catalog hierarchy and ordering.",
                        iconPath: "M5 4h14",
                    },
                    {
                        to: "/admin/products",
                        title: "Products",
                        description: "Review and maintain product list.",
                        iconPath: "M4 7.5",
                    },
                ],
            },
            global: {
                stubs: {
                    RouterLink: RouterLinkStub,
                },
            },
        });

        const links = wrapper.findAllComponents(RouterLinkStub);
        expect(links).toHaveLength(2);
        expect(wrapper.text()).toContain("Categories");
        expect(wrapper.text()).toContain("Products");
    });
});
