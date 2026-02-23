<template>
    <input v-model="model" :type="type" v-bind="attrs" />
</template>

<script setup lang="ts">
import { useAttrs } from "vue";

defineOptions({
    inheritAttrs: false,
});

withDefaults(
    defineProps<{
        type?: "text" | "email" | "password" | "tel" | "url";
    }>(),
    {
        type: "text",
    },
);

const attrs = useAttrs();
const model = defineModel<string>({ required: true });
</script>
