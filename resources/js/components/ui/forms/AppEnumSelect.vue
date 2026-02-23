<template>
    <AppSelectInput v-model="model" :disabled="disabled" @change="$emit('change')">
        <option v-for="option in options" :key="option.value" :value="option.value">
            {{ option.label }}
        </option>
    </AppSelectInput>
</template>

<script setup lang="ts">
import AppSelectInput from "@/components/ui/forms/AppSelectInput.vue";

export interface EnumSelectOption {
    value: string;
    label: string;
}

withDefaults(
    defineProps<{
        options: EnumSelectOption[];
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
