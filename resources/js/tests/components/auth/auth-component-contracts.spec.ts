/* @vitest-environment jsdom */

import { mount } from "@vue/test-utils";
import { describe, expect, it } from "vitest";

import AuthHeroCard from "@/components/auth/AuthHeroCard.vue";
import AuthLoginForm from "@/components/auth/AuthLoginForm.vue";
import AuthModeSwitcher from "@/components/auth/AuthModeSwitcher.vue";
import AuthRegisterForm from "@/components/auth/AuthRegisterForm.vue";

describe("auth hero card contract", () => {
    it("renders hero content", () => {
        const wrapper = mount(AuthHeroCard);

        expect(wrapper.text()).toContain("Secure access");
        expect(wrapper.text()).toContain("Sign in to unlock role-based sections");
    });
});

describe("auth mode switcher contract", () => {
    it("renders mode title and emits toggle", async () => {
        const wrapper = mount(AuthModeSwitcher, {
            props: {
                isLoginMode: true,
            },
        });

        await wrapper.get("button").trigger("click");

        expect(wrapper.text()).toContain("Login");
        expect(wrapper.emitted("toggle")).toHaveLength(1);
    });
});

describe("auth login form contract", () => {
    it("emits model updates and submit", async () => {
        const wrapper = mount(AuthLoginForm, {
            props: {
                email: "",
                password: "",
                isSubmitting: false,
            },
        });

        await wrapper.get('input[type="email"]').setValue("user@example.com");
        await wrapper.get('input[type="password"]').setValue("secret");
        await wrapper.get("form").trigger("submit");

        expect(wrapper.emitted("update:email")?.[0]).toEqual(["user@example.com"]);
        expect(wrapper.emitted("update:password")?.[0]).toEqual(["secret"]);
        expect(wrapper.emitted("submit")).toHaveLength(1);
    });
});

describe("auth register form contract", () => {
    it("emits model updates and submit", async () => {
        const wrapper = mount(AuthRegisterForm, {
            props: {
                firstName: "",
                lastName: "",
                email: "",
                password: "",
                passwordConfirmation: "",
                isSubmitting: false,
            },
        });

        const inputs = wrapper.findAll("input");
        await inputs[0].setValue("John");
        await inputs[1].setValue("Doe");
        await inputs[2].setValue("john@example.com");
        await inputs[3].setValue("password");
        await inputs[4].setValue("password");
        await wrapper.get("form").trigger("submit");

        expect(wrapper.emitted("update:firstName")?.[0]).toEqual(["John"]);
        expect(wrapper.emitted("update:lastName")?.[0]).toEqual(["Doe"]);
        expect(wrapper.emitted("update:email")?.[0]).toEqual(["john@example.com"]);
        expect(wrapper.emitted("update:password")?.[0]).toEqual(["password"]);
        expect(wrapper.emitted("update:passwordConfirmation")?.[0]).toEqual(["password"]);
        expect(wrapper.emitted("submit")).toHaveLength(1);
    });
});
