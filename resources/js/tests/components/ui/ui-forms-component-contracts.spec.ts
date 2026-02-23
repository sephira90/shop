/* @vitest-environment jsdom */

import { describe, expect, it } from "vitest";

import AppCheckboxField from "@/components/ui/forms/AppCheckboxField.vue";
import AppCheckboxInput from "@/components/ui/forms/AppCheckboxInput.vue";
import AppDateTimeInput from "@/components/ui/forms/AppDateTimeInput.vue";
import AppEnumSelect from "@/components/ui/forms/AppEnumSelect.vue";
import AppFilterSelect from "@/components/ui/forms/AppFilterSelect.vue";
import AppFormField from "@/components/ui/forms/AppFormField.vue";
import AppFormLayout from "@/components/ui/forms/AppFormLayout.vue";
import AppFormShell from "@/components/ui/forms/AppFormShell.vue";
import AppNumberInput from "@/components/ui/forms/AppNumberInput.vue";
import AppQuantityInput from "@/components/ui/forms/AppQuantityInput.vue";
import AppSearchInput from "@/components/ui/forms/AppSearchInput.vue";
import AppSelectInput from "@/components/ui/forms/AppSelectInput.vue";
import AppTextareaInput from "@/components/ui/forms/AppTextareaInput.vue";
import AppTextInput from "@/components/ui/forms/AppTextInput.vue";
import { mount } from "./helpers/ui-test-helpers";

describe("app form layout contract", () => {
    it("renders form-grid wrapper and emits submit", async () => {
        const wrapper = mount(AppFormLayout, {
            props: {
                withTopSpacing: true,
            },
            slots: {
                default: "<button type='submit'>Save</button>",
            },
        });

        expect(wrapper.element.tagName).toBe("FORM");
        expect(wrapper.classes()).toContain("form-grid");
        expect(wrapper.classes()).toContain("actions--top");

        await wrapper.trigger("submit");
        expect(wrapper.emitted("submit")).toHaveLength(1);
    });
});

describe("app form shell contract", () => {
    it("renders grid wrapper with top spacing and emits submit", async () => {
        const wrapper = mount(AppFormShell, {
            props: {
                withTopSpacing: true,
            },
            slots: {
                default: "<button type='submit'>Save</button>",
            },
        });

        expect(wrapper.element.tagName).toBe("FORM");
        expect(wrapper.classes()).toContain("grid");
        expect(wrapper.classes()).toContain("actions--top");

        await wrapper.trigger("submit");
        expect(wrapper.emitted("submit")).toHaveLength(1);
    });

    it("passes attrs to form root and supports disabling top spacing", () => {
        const wrapper = mount(AppFormShell, {
            props: {
                withTopSpacing: false,
            },
            attrs: {
                id: "profile-form",
                novalidate: "",
                "data-testid": "form-shell",
            },
        });

        expect(wrapper.attributes("id")).toBe("profile-form");
        expect(wrapper.attributes("novalidate")).toBeDefined();
        expect(wrapper.attributes("data-testid")).toBe("form-shell");
        expect(wrapper.classes()).not.toContain("actions--top");
    });
});

describe("app form field contract", () => {
    it("renders field label and slot content", () => {
        const wrapper = mount(AppFormField, {
            props: {
                label: "Email",
            },
            slots: {
                default: "<input type='email' value='test@example.com' />",
            },
        });

        expect(wrapper.element.tagName).toBe("LABEL");
        expect(wrapper.classes()).toContain("field");
        expect(wrapper.find(".field__label").text()).toBe("Email");
        expect(wrapper.find("input[type='email']").exists()).toBe(true);
    });

    it("passes attrs to label root and keeps slot stability", () => {
        const wrapper = mount(AppFormField, {
            props: {
                label: "Status",
            },
            attrs: {
                id: "status-field",
                "data-testid": "status-field",
                class: "field-extra",
            },
            slots: {
                default: "<select><option>Draft</option></select><small>Helper text</small>",
            },
        });

        expect(wrapper.classes()).toContain("field");
        expect(wrapper.classes()).toContain("field-extra");
        expect(wrapper.attributes("id")).toBe("status-field");
        expect(wrapper.attributes("data-testid")).toBe("status-field");
        expect(wrapper.find("select").exists()).toBe(true);
        expect(wrapper.text()).toContain("Helper text");
    });
});

describe("app text input contract", () => {
    it("updates model and supports explicit input type", async () => {
        const wrapper = mount(AppTextInput, {
            props: {
                modelValue: "",
                type: "email",
                placeholder: "Email",
                required: true,
            },
        });

        await wrapper.get("input").setValue("user@example.com");

        expect(wrapper.get("input").attributes("type")).toBe("email");
        expect(wrapper.emitted("update:modelValue")?.[0]).toEqual(["user@example.com"]);
    });

    it("passes disabled and readonly attrs to native input", () => {
        const wrapper = mount(AppTextInput, {
            props: {
                modelValue: "readonly@example.com",
                type: "text",
            },
            attrs: {
                disabled: "",
                readonly: "",
                autocomplete: "email",
                "data-testid": "text-input",
            },
        });

        const input = wrapper.get("input");
        expect(input.attributes("disabled")).toBeDefined();
        expect(input.attributes("readonly")).toBeDefined();
        expect(input.attributes("autocomplete")).toBe("email");
        expect(input.attributes("data-testid")).toBe("text-input");
    });
});

describe("app number input contract", () => {
    it("updates model and supports number modifier", async () => {
        const wrapper = mount(AppNumberInput, {
            props: {
                modelValue: 0,
                modelModifiers: { number: true },
                min: "0",
                step: "0.01",
            },
        });

        await wrapper.get("input").setValue("15.5");

        expect(wrapper.get("input").attributes("type")).toBe("number");
        expect(wrapper.emitted("update:modelValue")?.[0]).toEqual([15.5]);
    });

    it("passes attrs to native number input", () => {
        const wrapper = mount(AppNumberInput, {
            props: {
                modelValue: 42,
            },
            attrs: {
                disabled: "",
                readonly: "",
                min: "1",
                max: "99",
                "data-testid": "number-input",
            },
        });

        const input = wrapper.get("input");
        expect(input.attributes("disabled")).toBeDefined();
        expect(input.attributes("readonly")).toBeDefined();
        expect(input.attributes("min")).toBe("1");
        expect(input.attributes("max")).toBe("99");
        expect(input.attributes("data-testid")).toBe("number-input");
    });
});

describe("app quantity input contract", () => {
    it("normalizes quantity by min/max bounds", async () => {
        const wrapper = mount(AppQuantityInput, {
            props: {
                modelValue: 2,
                min: 1,
                max: 10,
            },
        });

        await wrapper.get("input").setValue("20");
        await wrapper.get("input").trigger("change");
        await wrapper.get("input").setValue("0");
        await wrapper.get("input").trigger("blur");

        const quantityUpdates = (wrapper.emitted("update:modelValue") ?? []).map(
            (eventPayload) => eventPayload[0],
        );

        expect(quantityUpdates).toContain(10);
        expect(quantityUpdates).toContain(1);
        expect(wrapper.emitted("change")?.length ?? 0).toBeGreaterThanOrEqual(1);
        expect(wrapper.emitted("blur")?.length ?? 0).toBeGreaterThanOrEqual(1);
    });

    it("passes attrs and handles integer normalization edge-cases", async () => {
        const wrapper = mount(AppQuantityInput, {
            props: {
                modelValue: 5,
                min: 2,
                max: 7,
                disabled: true,
                readonly: true,
            },
            attrs: {
                id: "cart-quantity-input",
                "data-testid": "quantity-input",
            },
        });

        const input = wrapper.get("input");
        expect(input.attributes("disabled")).toBeDefined();
        expect(input.attributes("readonly")).toBeDefined();
        expect(input.attributes("id")).toBe("cart-quantity-input");
        expect(input.attributes("data-testid")).toBe("quantity-input");

        await wrapper.setProps({
            disabled: false,
            readonly: false,
            modelValue: 5,
        });

        await input.setValue("6.8");
        await input.trigger("change");
        await input.setValue("abc");
        await input.trigger("blur");

        const quantityUpdates = (wrapper.emitted("update:modelValue") ?? []).map(
            (eventPayload) => eventPayload[0],
        );

        expect(quantityUpdates).toContain(6);
        expect(quantityUpdates).toContain(5);
        expect(wrapper.emitted("change")?.length ?? 0).toBeGreaterThanOrEqual(1);
        expect(wrapper.emitted("blur")?.length ?? 0).toBeGreaterThanOrEqual(1);
    });
});

describe("app datetime input contract", () => {
    it("updates model with datetime-local value", async () => {
        const wrapper = mount(AppDateTimeInput, {
            props: {
                modelValue: "",
            },
        });

        await wrapper.get("input").setValue("2026-02-23T13:45");

        expect(wrapper.get("input").attributes("type")).toBe("datetime-local");
        expect(wrapper.emitted("update:modelValue")?.[0]).toEqual(["2026-02-23T13:45"]);
    });

    it("passes readonly attrs to datetime input", () => {
        const wrapper = mount(AppDateTimeInput, {
            props: {
                modelValue: "2026-02-23T13:45",
            },
            attrs: {
                readonly: "",
                disabled: "",
                "data-testid": "datetime-input",
            },
        });

        const input = wrapper.get("input");
        expect(input.attributes("readonly")).toBeDefined();
        expect(input.attributes("disabled")).toBeDefined();
        expect(input.attributes("data-testid")).toBe("datetime-input");
    });
});

describe("app textarea input contract", () => {
    it("updates model and passes attrs", async () => {
        const wrapper = mount(AppTextareaInput, {
            props: {
                modelValue: "",
                rows: "4",
                placeholder: "Details",
            },
        });

        await wrapper.get("textarea").setValue("Updated description");

        expect(wrapper.get("textarea").attributes("rows")).toBe("4");
        expect(wrapper.emitted("update:modelValue")?.[0]).toEqual(["Updated description"]);
    });

    it("passes disabled and readonly attrs to textarea", () => {
        const wrapper = mount(AppTextareaInput, {
            props: {
                modelValue: "Initial value",
            },
            attrs: {
                disabled: "",
                readonly: "",
                "data-testid": "textarea-input",
            },
        });

        const textarea = wrapper.get("textarea");
        expect(textarea.attributes("disabled")).toBeDefined();
        expect(textarea.attributes("readonly")).toBeDefined();
        expect(textarea.attributes("data-testid")).toBe("textarea-input");
    });
});

describe("app search input contract", () => {
    it("updates model and emits enter event", async () => {
        const wrapper = mount(AppSearchInput, {
            props: {
                modelValue: "",
                placeholder: "Search products",
                disabled: false,
            },
        });

        await wrapper.get("input").setValue("boots");
        await wrapper.get("input").trigger("keyup.enter");

        expect(wrapper.emitted("update:modelValue")?.[0]).toEqual(["boots"]);
        expect(wrapper.emitted("enter")).toHaveLength(1);
    });

    it("passes attrs and emits enter only for Enter key", async () => {
        const wrapper = mount(AppSearchInput, {
            props: {
                modelValue: "",
                disabled: true,
            },
            attrs: {
                id: "orders-search",
                name: "orders-search",
                "data-testid": "search-input",
                "aria-label": "Search orders",
            },
        });

        const input = wrapper.get("input");
        expect(input.attributes("disabled")).toBeDefined();
        expect(input.attributes("id")).toBe("orders-search");
        expect(input.attributes("name")).toBe("orders-search");
        expect(input.attributes("data-testid")).toBe("search-input");
        expect(input.attributes("aria-label")).toBe("Search orders");

        await input.trigger("keyup");
        expect(wrapper.emitted("enter")).toBeUndefined();

        await wrapper.setProps({ disabled: false });
        await input.trigger("keyup.enter");
        expect(wrapper.emitted("enter")).toHaveLength(1);
    });
});

describe("app filter select contract", () => {
    it("updates model and emits change event", async () => {
        const wrapper = mount(AppFilterSelect, {
            props: {
                modelValue: "all",
                disabled: false,
            },
            slots: {
                default: `
                    <option value="all">All</option>
                    <option value="active">Active</option>
                `,
            },
        });

        await wrapper.get("select").setValue("active");

        expect(wrapper.emitted("update:modelValue")?.[0]).toEqual(["active"]);
        expect(wrapper.emitted("change")).toHaveLength(1);
    });

    it("passes attrs and disabled state to nested select", async () => {
        const wrapper = mount(AppFilterSelect, {
            props: {
                modelValue: "all",
                disabled: true,
            },
            attrs: {
                id: "filter-status",
                name: "filter-status",
                "data-testid": "filter-select",
            },
            slots: {
                default: `
                    <option value="all">All</option>
                    <option value="active">Active</option>
                `,
            },
        });

        const select = wrapper.get("select");
        expect(select.attributes("disabled")).toBeDefined();
        expect(select.attributes("id")).toBe("filter-status");
        expect(select.attributes("name")).toBe("filter-status");
        expect(select.attributes("data-testid")).toBe("filter-select");

        await wrapper.setProps({ disabled: false });
        await select.setValue("active");

        expect(wrapper.emitted("update:modelValue")?.[0]).toEqual(["active"]);
        expect(wrapper.emitted("change")?.length ?? 0).toBeGreaterThanOrEqual(1);
    });
});

describe("app select input contract", () => {
    it("updates model and supports number modifier", async () => {
        const wrapper = mount(AppSelectInput, {
            props: {
                modelValue: 1,
                modelModifiers: { number: true },
            },
            slots: {
                default: `
                    <option value="1">One</option>
                    <option value="2">Two</option>
                `,
            },
        });

        await wrapper.get("select").setValue("2");

        expect(wrapper.emitted("update:modelValue")?.[0]).toEqual([2]);
        expect(wrapper.emitted("change")).toHaveLength(1);
    });

    it("passes attrs and disabled state to native select", () => {
        const wrapper = mount(AppSelectInput, {
            props: {
                modelValue: "all",
                disabled: true,
            },
            attrs: {
                id: "orders-status-select",
                "data-testid": "status-select",
            },
            slots: {
                default: `
                    <option value="all">All</option>
                    <option value="pending">Pending</option>
                `,
            },
        });

        const select = wrapper.get("select");
        expect(select.attributes("disabled")).toBeDefined();
        expect(select.attributes("id")).toBe("orders-status-select");
        expect(select.attributes("data-testid")).toBe("status-select");
    });
});

describe("app enum select contract", () => {
    it("renders options and updates model", async () => {
        const wrapper = mount(AppEnumSelect, {
            props: {
                modelValue: "draft",
                options: [
                    { value: "draft", label: "Draft" },
                    { value: "active", label: "Active" },
                ],
            },
        });

        await wrapper.get("select").setValue("active");

        expect(wrapper.findAll("option")).toHaveLength(2);
        expect(wrapper.emitted("update:modelValue")?.[0]).toEqual(["active"]);
        expect(wrapper.emitted("change")).toHaveLength(1);
    });

    it("passes attrs and disabled state to nested select", async () => {
        const wrapper = mount(AppEnumSelect, {
            props: {
                modelValue: "draft",
                disabled: true,
                options: [
                    { value: "draft", label: "Draft" },
                    { value: "active", label: "Active" },
                    { value: "archived", label: "Archived" },
                ],
            },
            attrs: {
                id: "enum-status",
                "data-testid": "enum-select",
            },
        });

        const select = wrapper.get("select");
        expect(select.attributes("disabled")).toBeDefined();
        expect(select.attributes("id")).toBe("enum-status");
        expect(select.attributes("data-testid")).toBe("enum-select");
        expect(wrapper.findAll("option")).toHaveLength(3);

        await wrapper.setProps({ disabled: false });
        await select.setValue("active");

        expect(wrapper.emitted("update:modelValue")?.[0]).toEqual(["active"]);
        expect(wrapper.emitted("change")?.length ?? 0).toBeGreaterThanOrEqual(1);
    });
});

describe("app checkbox field contract", () => {
    it("renders checkbox field wrapper", () => {
        const wrapper = mount(AppCheckboxField, {
            slots: {
                default: "<input type='checkbox' checked /><span>Active</span>",
            },
        });

        expect(wrapper.element.tagName).toBe("LABEL");
        expect(wrapper.classes()).toContain("checkbox-field");
        expect(wrapper.find("input[type='checkbox']").exists()).toBe(true);
        expect(wrapper.text()).toContain("Active");
    });
});

describe("app checkbox input contract", () => {
    it("updates boolean model and supports disabled state", async () => {
        const wrapper = mount(AppCheckboxInput, {
            props: {
                modelValue: false,
                disabled: true,
            },
        });

        expect(wrapper.get("input").attributes("type")).toBe("checkbox");
        expect(wrapper.get("input").attributes("disabled")).toBeDefined();

        await wrapper.setProps({ disabled: false });
        await wrapper.get("input").setValue(true);

        expect(wrapper.emitted("update:modelValue")?.[0]).toEqual([true]);
    });

    it("passes attrs to native checkbox and keeps model binding", async () => {
        const wrapper = mount(AppCheckboxInput, {
            props: {
                modelValue: true,
                disabled: false,
            },
            attrs: {
                id: "campaign-enabled",
                name: "campaign-enabled",
                "data-testid": "checkbox-input",
                "aria-label": "Campaign enabled",
            },
        });

        const input = wrapper.get("input");
        expect(input.attributes("id")).toBe("campaign-enabled");
        expect(input.attributes("name")).toBe("campaign-enabled");
        expect(input.attributes("data-testid")).toBe("checkbox-input");
        expect(input.attributes("aria-label")).toBe("Campaign enabled");
        expect((input.element as HTMLInputElement).checked).toBe(true);

        await input.setValue(false);
        expect(wrapper.emitted("update:modelValue")?.[0]).toEqual([false]);
    });
});
