<template>
    <component
        :is="inCard ? AppCard : 'article'"
        :tag="inCard ? 'article' : undefined"
        :class="['metric-card', isSoft ? 'metric-card--soft' : null]"
    >
        <span class="metric-card__label">{{ label }}</span>
        <strong class="metric-card__value">{{ value }}</strong>
    </component>
</template>

<script setup lang="ts">
import { computed } from "vue";

import AppCard from "@/components/ui/AppCard.vue";

type MetricCardVariant = "default" | "soft";

const props = withDefaults(
    defineProps<{
        label: string;
        value: string | number;
        inCard?: boolean;
        variant?: MetricCardVariant;
        soft?: boolean;
    }>(),
    {
        inCard: false,
        variant: undefined,
        soft: false,
    },
);

const isSoft = computed((): boolean => {
    if (props.variant !== undefined) {
        return props.variant === "soft";
    }

    return props.soft;
});
</script>
