<template>
    <AppPaginationBar
        class="actions--top"
        :page="page"
        :meta="meta"
        :is-loading="isLoading"
        total-label="Total orders"
        @load-prev="$emit('loadPrev')"
        @load-next="$emit('loadNext')"
    />
</template>

<script setup lang="ts">
import AppPaginationBar from "@/components/ui/AppPaginationBar.vue";
import type { PaginationMeta } from "@/types/pagination";

defineProps<{
    page: number;
    meta: PaginationMeta;
    isLoading: boolean;
}>();

defineEmits<{
    (event: "loadPrev"): void;
    (event: "loadNext"): void;
}>();
</script>
