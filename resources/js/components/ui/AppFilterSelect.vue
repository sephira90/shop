<template>
    <AppSelectInput v-model="model" :disabled="disabled" @change="$emit('change')">
        <slot />
    </AppSelectInput>
</template>

<script setup lang="ts">
import AppSelectInput from "@/components/ui/AppSelectInput.vue";

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

const model = defineModel<string>({ required: true });
</script>
