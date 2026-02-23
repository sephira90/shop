<template>
    <component
        :is="resolvedAs"
        v-bind="componentAttrs"
        :class="buttonClass"
        :type="nativeType"
        :disabled="nativeDisabled"
        :aria-disabled="nonButtonAriaDisabled"
        :tabindex="nonButtonTabIndex"
        @click="handleClick"
    >
        <slot />
    </component>
</template>

<script setup lang="ts">
import { computed, useAttrs } from "vue";
import type { Component } from "vue";
import { RouterLink } from "vue-router";

defineOptions({
    inheritAttrs: false,
});

type AppButtonVariant = "primary" | "muted";
type AppButtonType = "button" | "submit" | "reset";

const props = withDefaults(
    defineProps<{
        as?: string | Component;
        variant?: AppButtonVariant;
        type?: AppButtonType;
        disabled?: boolean;
        to?: string | Record<string, unknown>;
        href?: string;
        target?: string;
        rel?: string;
    }>(),
    {
        as: undefined,
        variant: "muted",
        type: "button",
        disabled: false,
        to: undefined,
        href: undefined,
        target: undefined,
        rel: undefined,
    },
);

const attrs = useAttrs();

const resolvedAs = computed((): string | Component => {
    if (props.as) {
        return props.as;
    }

    if (props.to !== undefined) {
        return RouterLink;
    }

    if (props.href !== undefined) {
        return "a";
    }

    return "button";
});

const buttonClass = computed<string[]>(() => {
    return ["btn", props.variant === "primary" ? "btn-primary" : "btn-muted"];
});

const isNativeButton = computed((): boolean => {
    return typeof resolvedAs.value === "string" && resolvedAs.value.toLowerCase() === "button";
});

const isAnchor = computed((): boolean => {
    return typeof resolvedAs.value === "string" && resolvedAs.value.toLowerCase() === "a";
});

const isDisabledLink = computed((): boolean => {
    return props.disabled && !isNativeButton.value;
});

const resolvedRel = computed((): string | undefined => {
    if (props.rel) {
        return props.rel;
    }

    if (isAnchor.value && props.target === "_blank") {
        return "noopener noreferrer";
    }

    return undefined;
});

const componentAttrs = computed<Record<string, unknown>>(() => {
    const forwardedAttrs: Record<string, unknown> = { ...attrs };

    if (!isNativeButton.value) {
        if (props.to !== undefined) {
            forwardedAttrs.to = props.to;
        }

        if (props.href !== undefined && !isDisabledLink.value) {
            forwardedAttrs.href = props.href;
        }

        if (props.target !== undefined) {
            forwardedAttrs.target = props.target;
        }

        if (resolvedRel.value !== undefined) {
            forwardedAttrs.rel = resolvedRel.value;
        }
    }

    return forwardedAttrs;
});

const nativeType = computed<AppButtonType | undefined>(() => {
    if (isNativeButton.value) {
        return props.type;
    }

    return undefined;
});

const nativeDisabled = computed<boolean | undefined>(() => {
    if (!isNativeButton.value) {
        return undefined;
    }

    return props.disabled;
});

const nonButtonAriaDisabled = computed<string | undefined>(() => {
    if (!isDisabledLink.value) {
        return undefined;
    }

    return "true";
});

const nonButtonTabIndex = computed<number | undefined>(() => {
    if (!isDisabledLink.value) {
        return undefined;
    }

    return -1;
});

const handleClick = (event: { preventDefault: () => void; stopPropagation: () => void }): void => {
    if (!isDisabledLink.value) {
        return;
    }

    event.preventDefault();
    event.stopPropagation();
};
</script>
