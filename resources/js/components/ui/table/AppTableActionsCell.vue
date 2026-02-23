<template>
    <td v-bind="attrs">
        <AppActionsRow :with-top-spacing="withTopSpacing">
            <slot />
        </AppActionsRow>
    </td>
</template>

<script setup lang="ts">
import { useAttrs } from "vue";

import AppActionsRow from "@/components/ui/actions/AppActionsRow.vue";

defineOptions({
    inheritAttrs: false,
});

withDefaults(
    defineProps<{
        withTopSpacing?: boolean;
    }>(),
    {
        withTopSpacing: false,
    },
);

const attrs = useAttrs();
</script>
