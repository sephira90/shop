<template>
    <select v-model="model" :disabled="disabled" v-bind="attrs" @change="$emit('change')">
        <slot />
    </select>
</template>

<script setup lang="ts">
import { useAttrs } from "vue";

defineOptions({
    inheritAttrs: false,
});

withDefaults(
    defineProps<{
        disabled?: boolean;
    }>(),
    {
        disabled: false,
    },
);

defineEmits<{
    (event: "change"): void;
}>();

const attrs = useAttrs();
const [model, modifiers] = defineModel<string | number | null>({
    required: true,
    set(value) {
        if (!modifiers.number) {
            return value;
        }

        const normalized = String(value ?? "").trim();
        if (normalized === "") {
            return null;
        }

        const parsed = Number(normalized);
        return Number.isNaN(parsed) ? value : parsed;
    },
});
</script>
