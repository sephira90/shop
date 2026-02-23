<template>
    <input
        v-model="model"
        type="search"
        :placeholder="placeholder"
        :disabled="disabled"
        @keyup.enter="$emit('enter')"
    />
</template>

<script setup lang="ts">
withDefaults(
    defineProps<{
        placeholder?: string;
        disabled?: boolean;
    }>(),
    {
        placeholder: "Search",
        disabled: false,
    },
);

defineEmits<{
    (event: "enter"): void;
}>();

const model = defineModel<string>({ required: true });
</script>
