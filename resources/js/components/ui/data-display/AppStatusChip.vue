<template>
    <span :class="['status-chip', resolvedToneClass]">
        {{ label }}
    </span>
</template>

<script setup lang="ts">
import { computed } from "vue";

export type StatusChipTone = "neutral" | "good" | "warn" | "info" | "bad" | "role";

const toneClassMap: Record<StatusChipTone, string> = {
    neutral: "status-chip--neutral",
    good: "status-chip--good",
    warn: "status-chip--warn",
    info: "status-chip--info",
    bad: "status-chip--bad",
    role: "status-chip--role",
};

const props = withDefaults(
    defineProps<{
        label: string;
        tone?: StatusChipTone;
        toneClass?: string;
    }>(),
    {
        tone: "neutral",
        toneClass: undefined,
    },
);

const resolvedToneClass = computed((): string => {
    return props.toneClass ?? toneClassMap[props.tone];
});
</script>
