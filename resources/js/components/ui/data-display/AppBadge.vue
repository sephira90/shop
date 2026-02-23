<template>
    <span :class="['badge', resolvedToneClass]">
        {{ label }}
    </span>
</template>

<script setup lang="ts">
import { computed } from "vue";

export type BadgeTone = "active" | "inactive";

const toneClassMap: Record<BadgeTone, string> = {
    active: "badge--active",
    inactive: "badge--inactive",
};

const props = withDefaults(
    defineProps<{
        label: string;
        tone?: BadgeTone;
        toneClass?: string;
    }>(),
    {
        tone: "inactive",
        toneClass: undefined,
    },
);

const resolvedToneClass = computed((): string => {
    return props.toneClass ?? toneClassMap[props.tone];
});
</script>
