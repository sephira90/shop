/* @vitest-environment jsdom */

import { describe, expect, it } from "vitest";

import AppBadge from "@/components/ui/data-display/AppBadge.vue";
import AppDetailBox from "@/components/ui/data-display/AppDetailBox.vue";
import AppMetricCard from "@/components/ui/data-display/AppMetricCard.vue";
import AppPaginationBar from "@/components/ui/data-display/AppPaginationBar.vue";
import AppStatusChip from "@/components/ui/data-display/AppStatusChip.vue";
import AppStatusStack from "@/components/ui/data-display/AppStatusStack.vue";
import BooleanStatusChip from "@/components/ui/data-display/BooleanStatusChip.vue";
import { defaultPaginationMeta, mount } from "./helpers/ui-test-helpers";

const meta = defaultPaginationMeta;

describe("app status chip contract", () => {
    it("renders label with tone variant", () => {
        const wrapper = mount(AppStatusChip, {
            props: {
                label: "completed",
                tone: "good",
            },
        });

        expect(wrapper.classes()).toContain("status-chip");
        expect(wrapper.classes()).toContain("status-chip--good");
        expect(wrapper.text()).toContain("completed");
    });
});

describe("boolean status chip contract", () => {
    it("maps true/false values to default labels and tones", async () => {
        const wrapper = mount(BooleanStatusChip, {
            props: {
                value: true,
            },
        });

        expect(wrapper.find(".status-chip").exists()).toBe(true);
        expect(wrapper.text()).toContain("active");
        expect(wrapper.classes()).toContain("status-chip--good");

        await wrapper.setProps({ value: false });

        expect(wrapper.text()).toContain("inactive");
        expect(wrapper.classes()).toContain("status-chip--neutral");
    });

    it("supports custom labels and tones", () => {
        const wrapper = mount(BooleanStatusChip, {
            props: {
                value: false,
                trueLabel: "enabled",
                falseLabel: "disabled",
                trueTone: "info",
                falseTone: "bad",
            },
        });

        expect(wrapper.text()).toContain("disabled");
        expect(wrapper.classes()).toContain("status-chip--bad");
    });
});

describe("app badge contract", () => {
    it("renders label with tone variant", () => {
        const wrapper = mount(AppBadge, {
            props: {
                label: "active",
                tone: "active",
            },
        });

        expect(wrapper.classes()).toContain("badge");
        expect(wrapper.classes()).toContain("badge--active");
        expect(wrapper.text()).toContain("active");
    });
});

describe("app metric card contract", () => {
    it("renders label, value and supports variant/card modes", () => {
        const wrapper = mount(AppMetricCard, {
            props: {
                label: "Loaded orders",
                value: 12,
                variant: "soft",
                inCard: true,
            },
        });

        expect(wrapper.classes()).toContain("metric-card");
        expect(wrapper.classes()).toContain("metric-card--soft");
        expect(wrapper.classes()).toContain("card");
        expect(wrapper.text()).toContain("Loaded orders");
        expect(wrapper.text()).toContain("12");
    });
});

describe("app detail box contract", () => {
    it("renders title and muted content", () => {
        const wrapper = mount(AppDetailBox, {
            props: {
                title: "Billing address",
                content: "Main st, New York",
            },
        });

        expect(wrapper.classes()).toContain("order-detail-box");
        expect(wrapper.text()).toContain("Billing address");
        expect(wrapper.text()).toContain("Main st, New York");
    });
});

describe("app status stack contract", () => {
    it("renders multiple status chips", () => {
        const wrapper = mount(AppStatusStack, {
            props: {
                items: [
                    { key: "order", label: "pending", tone: "warn" },
                    { key: "payment", label: "captured", tone: "good" },
                    { key: "shipment", label: "shipped", tone: "info" },
                ],
            },
        });

        const chips = wrapper.findAll(".status-chip");
        expect(chips).toHaveLength(3);
        expect(wrapper.text()).toContain("pending");
        expect(wrapper.text()).toContain("captured");
        expect(wrapper.text()).toContain("shipped");
    });
});

describe("app pagination bar contract", () => {
    it("renders summary and emits pagination events", async () => {
        const wrapper = mount(AppPaginationBar, {
            props: {
                page: 2,
                meta,
                isLoading: false,
                totalLabel: "Total orders",
            },
        });

        expect(wrapper.text()).toContain("Page 2 of 5. Total orders: 120.");

        const buttons = wrapper.findAll("button");
        await buttons[0].trigger("click");
        await buttons[1].trigger("click");

        expect(wrapper.emitted("loadPrev")).toHaveLength(1);
        expect(wrapper.emitted("loadNext")).toHaveLength(1);
    });

    it("hides summary when showSummary is false", () => {
        const wrapper = mount(AppPaginationBar, {
            props: {
                page: 1,
                meta: {
                    ...meta,
                    current_page: 1,
                },
                isLoading: false,
                showSummary: false,
            },
        });

        expect(wrapper.text()).not.toContain("Page 1 of");
    });

    it("wraps pagination content in card when enabled", () => {
        const wrapper = mount(AppPaginationBar, {
            props: {
                page: 1,
                meta: {
                    ...meta,
                    current_page: 1,
                },
                isLoading: false,
                wrapInCard: true,
            },
        });

        expect(wrapper.classes()).toContain("card");
    });
});
