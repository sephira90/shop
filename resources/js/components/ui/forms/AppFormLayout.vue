<template>
    <form class="form-grid" :class="{ 'actions--top': withTopSpacing }" @submit.prevent="submit">
        <slot />
    </form>
</template>

<script setup lang="ts">
withDefaults(
    defineProps<{
        withTopSpacing?: boolean;
    }>(),
    {
        withTopSpacing: false,
    },
);

const emit = defineEmits<{
    (event: "submit"): void;
}>();

const submit = (): void => {
    emit("submit");
};
</script>
