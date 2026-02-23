/* @vitest-environment jsdom */

import { mount } from "@vue/test-utils";
import { describe, expect, it } from "vitest";

import CheckoutAddressCard from "@/components/checkout/CheckoutAddressCard.vue";
import CheckoutContactFields from "@/components/checkout/CheckoutContactFields.vue";
import CheckoutHeader from "@/components/checkout/CheckoutHeader.vue";
import CheckoutResultNotice from "@/components/checkout/CheckoutResultNotice.vue";
import type { CheckoutAddressForm } from "@/types/checkout";

describe("checkout header contract", () => {
    it("renders checkout title and subtitle", () => {
        const wrapper = mount(CheckoutHeader);

        expect(wrapper.text()).toContain("Checkout");
        expect(wrapper.text()).toContain("billing and shipping details");
    });
});

describe("checkout contact fields contract", () => {
    it("emits model updates for email and coupon", async () => {
        const wrapper = mount(CheckoutContactFields, {
            props: {
                email: "",
                couponCode: "",
            },
        });

        await wrapper.get('input[type="email"]').setValue("user@example.com");
        await wrapper.get('input[placeholder="Coupon code (optional)"]').setValue("TEST10");

        expect(wrapper.emitted("update:email")?.[0]).toEqual(["user@example.com"]);
        expect(wrapper.emitted("update:couponCode")?.[0]).toEqual(["TEST10"]);
    });
});

describe("checkout address card contract", () => {
    it("emits updated address object", async () => {
        const wrapper = mount(CheckoutAddressCard, {
            props: {
                title: "Billing address",
                address: {
                    line1: "1 Main",
                    city: "NY",
                    country: "US",
                    postcode: "10001",
                } as CheckoutAddressForm,
            },
        });

        await wrapper.get('input[placeholder="City"]').setValue("Boston");

        expect(wrapper.emitted("update:address")?.[0]).toEqual([
            {
                line1: "1 Main",
                city: "Boston",
                country: "US",
                postcode: "10001",
            },
        ]);
    });
});

describe("checkout result notice contract", () => {
    it("renders success and error states", () => {
        const successWrapper = mount(CheckoutResultNotice, {
            props: {
                message: "Order created: #123",
                isSuccess: true,
            },
        });
        const errorWrapper = mount(CheckoutResultNotice, {
            props: {
                message: "Checkout failed.",
                isSuccess: false,
            },
        });

        expect(successWrapper.classes()).toContain("notice--success");
        expect(errorWrapper.classes()).toContain("notice--error");
    });
});
