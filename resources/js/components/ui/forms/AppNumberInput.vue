<template>
    <input v-model="model" type="number" v-bind="attrs" />
</template>

<script setup lang="ts">
import { useAttrs } from "vue";

defineOptions({
    inheritAttrs: false,
});

const attrs = useAttrs();
const [model, modifiers] = defineModel<string | number>({
    required: true,
    set(value) {
        if (!modifiers.number) {
            return value;
        }

        const normalized = String(value ?? "").trim();
        if (normalized === "") {
            return value;
        }

        const parsed = Number(normalized);
        return Number.isNaN(parsed) ? value : parsed;
    },
});
</script>
