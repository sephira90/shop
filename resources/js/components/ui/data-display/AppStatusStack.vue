<template>
    <div class="status-stack">
        <AppStatusChip
            v-for="(item, index) in items"
            :key="item.key ?? `${item.label}-${index}`"
            :label="item.label"
            :tone="item.tone"
            :tone-class="item.toneClass"
        />
    </div>
</template>

<script setup lang="ts">
import AppStatusChip from "@/components/ui/data-display/AppStatusChip.vue";
import type { StatusChipTone } from "@/components/ui/data-display/AppStatusChip.vue";

interface StatusStackItem {
    label: string;
    tone?: StatusChipTone;
    toneClass?: string;
    key?: string | number;
}

defineProps<{
    items: StatusStackItem[];
}>();
</script>
