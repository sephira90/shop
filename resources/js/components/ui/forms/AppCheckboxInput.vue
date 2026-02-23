<template>
    <input v-model="model" type="checkbox" :disabled="disabled" v-bind="attrs" />
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

const attrs = useAttrs();
const model = defineModel<boolean>({ required: true });
</script>
