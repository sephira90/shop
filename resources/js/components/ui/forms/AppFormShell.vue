<template>
    <form
        class="grid"
        :class="{ 'actions--top': withTopSpacing }"
        v-bind="attrs"
        @submit.prevent="submit"
    >
        <slot />
    </form>
</template>

<script setup lang="ts">
import { useAttrs } from "vue";

defineOptions({
    inheritAttrs: false,
});

withDefaults(
    defineProps<{
        withTopSpacing?: boolean;
    }>(),
    {
        withTopSpacing: true,
    },
);

const emit = defineEmits<{
    (event: "submit"): void;
}>();

const attrs = useAttrs();

const submit = (): void => {
    emit("submit");
};
</script>
