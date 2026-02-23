/* @vitest-environment jsdom */

import { RouterLinkStub, mount } from "@vue/test-utils";
import { describe, expect, it } from "vitest";

import AppBadge from "@/components/ui/AppBadge.vue";
import AppActionsRow from "@/components/ui/AppActionsRow.vue";
import AppButton from "@/components/ui/AppButton.vue";
import AppCard from "@/components/ui/AppCard.vue";
import AppCheckboxField from "@/components/ui/AppCheckboxField.vue";
import AppCheckboxInput from "@/components/ui/AppCheckboxInput.vue";
import AppDetailBox from "@/components/ui/AppDetailBox.vue";
import AppDateTimeInput from "@/components/ui/AppDateTimeInput.vue";
import AppEmptyState from "@/components/ui/AppEmptyState.vue";
import AppFilterSelect from "@/components/ui/AppFilterSelect.vue";
import AppFormField from "@/components/ui/AppFormField.vue";
import AppFormLayout from "@/components/ui/AppFormLayout.vue";
import AppFormShell from "@/components/ui/AppFormShell.vue";
import AppGridThreeColumns from "@/components/ui/AppGridThreeColumns.vue";
import AppGridTwoColumns from "@/components/ui/AppGridTwoColumns.vue";
import AppMetricCard from "@/components/ui/AppMetricCard.vue";
import AppMutedText from "@/components/ui/AppMutedText.vue";
import AppNotice from "@/components/ui/AppNotice.vue";
import AppNumberInput from "@/components/ui/AppNumberInput.vue";
import AppPaginationBar from "@/components/ui/AppPaginationBar.vue";
import AppQuantityInput from "@/components/ui/AppQuantityInput.vue";
import AppSearchInput from "@/components/ui/AppSearchInput.vue";
import AppSelectInput from "@/components/ui/AppSelectInput.vue";
import AppSectionTitle from "@/components/ui/AppSectionTitle.vue";
import AppStatusChip from "@/components/ui/AppStatusChip.vue";
import AppStackBetween from "@/components/ui/AppStackBetween.vue";
import AppStatusStack from "@/components/ui/AppStatusStack.vue";
import AppSubmitResetActions from "@/components/ui/AppSubmitResetActions.vue";
import AppTableActionsCell from "@/components/ui/AppTableActionsCell.vue";
import AppTableEmptyStateRow from "@/components/ui/AppTableEmptyStateRow.vue";
import AppTableSection from "@/components/ui/AppTableSection.vue";
import AppTextareaInput from "@/components/ui/AppTextareaInput.vue";
import AppTextInput from "@/components/ui/AppTextInput.vue";
import BooleanStatusChip from "@/components/ui/BooleanStatusChip.vue";
import AppEnumSelect from "@/components/ui/AppEnumSelect.vue";
import type { PaginationMeta } from "@/types/pagination";

const meta: PaginationMeta = {
    current_page: 2,
    last_page: 5,
    total: 120,
    per_page: 30,
};

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

describe("app card contract", () => {
    it("renders card class and custom tag", () => {
        const wrapper = mount(AppCard, {
            props: {
                tag: "section",
            },
            slots: {
                default: "<p>Content</p>",
            },
        });

        expect(wrapper.element.tagName).toBe("SECTION");
        expect(wrapper.classes()).toContain("card");
        expect(wrapper.text()).toContain("Content");
    });
});

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

describe("app grid two columns contract", () => {
    it("renders grid-2 wrapper with custom tag", () => {
        const wrapper = mount(AppGridTwoColumns, {
            props: {
                tag: "section",
                withTopSpacing: true,
            },
            slots: {
                default: "<p>Column A</p><p>Column B</p>",
            },
        });

        expect(wrapper.element.tagName).toBe("SECTION");
        expect(wrapper.classes()).toContain("grid");
        expect(wrapper.classes()).toContain("grid-2");
        expect(wrapper.classes()).toContain("actions--top");
        expect(wrapper.text()).toContain("Column A");
        expect(wrapper.text()).toContain("Column B");
    });
});

describe("app grid three columns contract", () => {
    it("renders grid-3 wrapper with custom tag", () => {
        const wrapper = mount(AppGridThreeColumns, {
            props: {
                tag: "section",
                withTopSpacing: true,
            },
            slots: {
                default: "<p>Column A</p><p>Column B</p><p>Column C</p>",
            },
        });

        expect(wrapper.element.tagName).toBe("SECTION");
        expect(wrapper.classes()).toContain("grid");
        expect(wrapper.classes()).toContain("grid-3");
        expect(wrapper.classes()).toContain("actions--top");
        expect(wrapper.text()).toContain("Column A");
        expect(wrapper.text()).toContain("Column B");
        expect(wrapper.text()).toContain("Column C");
    });
});

describe("app table section contract", () => {
    it("renders table wrapper and optional top spacing class", () => {
        const wrapper = mount(AppTableSection, {
            props: {
                withTopSpacing: true,
            },
            slots: {
                default: "<tbody><tr><td>Row</td></tr></tbody>",
            },
        });

        expect(wrapper.classes()).toContain("table-wrap");
        expect(wrapper.classes()).toContain("actions--top");
        expect(wrapper.find("table.table").exists()).toBe(true);
        expect(wrapper.text()).toContain("Row");
    });
});

describe("app table actions cell contract", () => {
    it("renders td wrapper with actions layout and attrs passthrough", () => {
        const wrapper = mount(AppTableActionsCell, {
            attrs: {
                class: "actions-column",
                "data-testid": "table-actions-cell",
            },
            slots: {
                default: "<button>Edit</button><button>Delete</button>",
            },
        });

        expect(wrapper.element.tagName).toBe("TD");
        expect(wrapper.classes()).toContain("actions-column");
        expect(wrapper.attributes("data-testid")).toBe("table-actions-cell");
        expect(wrapper.find(".actions").exists()).toBe(true);
        expect(wrapper.text()).toContain("Edit");
        expect(wrapper.text()).toContain("Delete");
    });

    it("supports top spacing layout mode", () => {
        const wrapper = mount(AppTableActionsCell, {
            props: {
                withTopSpacing: true,
            },
            slots: {
                default: "<button>Action</button>",
            },
        });

        expect(wrapper.find(".actions").classes()).toContain("actions--top");
    });
});

describe("app table empty state row contract", () => {
    it("renders row/colspan and delegated empty-state message", () => {
        const wrapper = mount(AppTableEmptyStateRow, {
            props: {
                colspan: 6,
                message: "No records found",
            },
        });

        expect(wrapper.element.tagName).toBe("TR");
        expect(wrapper.get("td").attributes("colspan")).toBe("6");
        expect(wrapper.text()).toContain("No records found");
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
