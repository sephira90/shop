<template>
    <AppStatusChip :label="label" :tone="tone" />
</template>

<script setup lang="ts">
import { computed } from "vue";

import AppStatusChip, { type StatusChipTone } from "@/components/ui/data-display/AppStatusChip.vue";

const props = withDefaults(
    defineProps<{
        value: boolean;
        trueLabel?: string;
        falseLabel?: string;
        trueTone?: StatusChipTone;
        falseTone?: StatusChipTone;
    }>(),
    {
        trueLabel: "active",
        falseLabel: "inactive",
        trueTone: "good",
        falseTone: "neutral",
    },
);

const label = computed((): string => {
    return props.value ? props.trueLabel : props.falseLabel;
});

const tone = computed((): StatusChipTone => {
    return props.value ? props.trueTone : props.falseTone;
});
</script>
