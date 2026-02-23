<template>
    <textarea v-model="model" v-bind="attrs" />
</template>

<script setup lang="ts">
import { useAttrs } from "vue";

defineOptions({
    inheritAttrs: false,
});

const attrs = useAttrs();
const model = defineModel<string>({ required: true });
</script>
