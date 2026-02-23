<template>
    <p :class="['notice', variantClass]">
        {{ message }}
    </p>
</template>

<script setup lang="ts">
import { computed } from "vue";

type NoticeVariant = "success" | "error";

const props = withDefaults(
    defineProps<{
        message: string;
        variant?: NoticeVariant;
    }>(),
    {
        variant: "error",
    },
);

const variantClass = computed((): string => {
    return props.variant === "success" ? "notice--success" : "notice--error";
});
</script>
