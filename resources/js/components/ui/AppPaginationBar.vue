<template>
    <component :is="wrapInCard ? AppCard : 'div'">
        <AppStackBetween>
            <p v-if="showSummary" class="muted">
                Page {{ meta.current_page }} of {{ meta.last_page }}. {{ totalLabel }}:
                {{ meta.total }}.
            </p>
            <AppActionsRow>
                <AppButton
                    variant="muted"
                    type="button"
                    :disabled="page <= 1 || isLoading"
                    @click="$emit('loadPrev')"
                >
                    Previous
                </AppButton>
                <AppButton
                    variant="muted"
                    type="button"
                    :disabled="page >= meta.last_page || isLoading"
                    @click="$emit('loadNext')"
                >
                    Next
                </AppButton>
            </AppActionsRow>
        </AppStackBetween>
    </component>
</template>

<script setup lang="ts">
import AppActionsRow from "@/components/ui/AppActionsRow.vue";
import AppButton from "@/components/ui/AppButton.vue";
import AppCard from "@/components/ui/AppCard.vue";
import AppStackBetween from "@/components/ui/AppStackBetween.vue";
import type { PaginationMeta } from "@/types/pagination";

withDefaults(
    defineProps<{
        page: number;
        meta: PaginationMeta;
        isLoading: boolean;
        totalLabel?: string;
        showSummary?: boolean;
        wrapInCard?: boolean;
    }>(),
    {
        totalLabel: "Total",
        showSummary: true,
        wrapInCard: false,
    },
);

defineEmits<{
    (event: "loadPrev"): void;
    (event: "loadNext"): void;
}>();
</script>
