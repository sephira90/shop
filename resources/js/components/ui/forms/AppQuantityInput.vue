<template>
    <input
        :value="modelValue"
        type="number"
        :min="min"
        :max="max"
        step="1"
        :disabled="disabled"
        :readonly="readonly"
        v-bind="attrs"
        @change="handleChange"
        @blur="handleBlur"
    />
</template>

<script setup lang="ts">
import { useAttrs } from "vue";

defineOptions({
    inheritAttrs: false,
});

const props = withDefaults(
    defineProps<{
        modelValue: number;
        min?: number;
        max?: number;
        disabled?: boolean;
        readonly?: boolean;
    }>(),
    {
        min: 1,
        max: 1000,
        disabled: false,
        readonly: false,
    },
);

const emit = defineEmits<{
    (event: "update:modelValue", value: number): void;
    (event: "change"): void;
    (event: "blur"): void;
}>();

const attrs = useAttrs();

const normalizeQuantity = (value: number): number => {
    const integerValue = Math.trunc(value);
    return Math.min(props.max, Math.max(props.min, integerValue));
};

const parseQuantity = (rawValue: string): number => {
    const parsed = Number.parseInt(rawValue, 10);

    if (!Number.isFinite(parsed)) {
        return props.modelValue;
    }

    return normalizeQuantity(parsed);
};

const handleChange = (event: unknown): void => {
    const target = (event as { target?: { value?: string } }).target;
    const nextQuantity =
        target && typeof target.value === "string" ? parseQuantity(target.value) : props.modelValue;

    if (target && typeof target.value === "string") {
        target.value = String(nextQuantity);
    }

    emit("update:modelValue", nextQuantity);
    emit("change");
};

const handleBlur = (event: unknown): void => {
    const target = (event as { target?: { value?: string } }).target;
    const nextQuantity =
        target && typeof target.value === "string" ? parseQuantity(target.value) : props.modelValue;

    if (target && typeof target.value === "string") {
        target.value = String(nextQuantity);
    }

    emit("update:modelValue", nextQuantity);
    emit("blur");
};
</script>
