/* @vitest-environment jsdom */

import { RouterLinkStub, mount } from "@vue/test-utils";
import { describe, expect, it } from "vitest";

import AccountHeroCard from "@/components/account/profile/AccountHeroCard.vue";
import AccountMetricsGrid from "@/components/account/profile/AccountMetricsGrid.vue";
import AccountProfileFormCard from "@/components/account/profile/AccountProfileFormCard.vue";
import AccountProfileSummaryCard from "@/components/account/profile/AccountProfileSummaryCard.vue";

describe("account profile hero contract", () => {
    it("renders profile identity and role badges", () => {
        const wrapper = mount(AccountHeroCard, {
            props: {
                profileName: "Jane Doe",
                profileInitial: "J",
                profileEmail: "jane@example.com",
                verificationLabel: "Email verified",
                verificationTone: "good",
                roleLabels: ["Customer", "Manager"],
            },
        });

        expect(wrapper.text()).toContain("Jane Doe");
        expect(wrapper.text()).toContain("jane@example.com");
        expect(wrapper.text()).toContain("Email verified");
        expect(wrapper.text()).toContain("Customer");
        expect(wrapper.text()).toContain("Manager");
    });
});

describe("account profile metrics grid contract", () => {
    it("renders numeric metrics and formatted total", () => {
        const wrapper = mount(AccountMetricsGrid, {
            props: {
                metrics: {
                    totalOrders: 12,
                    paidOrders: 10,
                    inDelivery: 3,
                    loadedTotalSpent: 845.5,
                },
                formatPrice: (value: number) => `USD ${value}`,
            },
        });

        expect(wrapper.text()).toContain("12");
        expect(wrapper.text()).toContain("10");
        expect(wrapper.text()).toContain("3");
        expect(wrapper.text()).toContain("USD 845.5");
    });
});

describe("account profile form card contract", () => {
    it("updates model and emits submit/reset events", async () => {
        const wrapper = mount(AccountProfileFormCard, {
            props: {
                form: {
                    first_name: "Jane",
                    last_name: "Doe",
                    phone: "",
                },
                isSavingProfile: false,
                profileEmail: "jane@example.com",
                noticeType: "success",
                noticeMessage: "Profile updated successfully.",
            },
        });

        await wrapper.get("input[maxlength='80']").setValue("Janet");
        await wrapper.get("form").trigger("submit");
        await wrapper.findAll("button")[1].trigger("click");

        expect(wrapper.emitted("submit")).toHaveLength(1);
        expect(wrapper.emitted("reset")).toHaveLength(1);
        expect(wrapper.text()).toContain("Profile updated successfully.");
    });
});

describe("account profile summary card contract", () => {
    it("renders quick links and hides admin link when access denied", () => {
        const wrapper = mount(AccountProfileSummaryCard, {
            props: {
                profileName: "Jane Doe",
                profileEmail: "jane@example.com",
                profilePhone: "+15551234567",
                roleLabels: ["Customer"],
                canAccessAdmin: false,
            },
            global: {
                stubs: {
                    RouterLink: RouterLinkStub,
                },
            },
        });

        const links = wrapper.findAllComponents(RouterLinkStub);
        expect(links).toHaveLength(2);
        expect(wrapper.text()).not.toContain("Open admin");
    });
});
