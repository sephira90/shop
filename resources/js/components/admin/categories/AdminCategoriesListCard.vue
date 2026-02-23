<template>
    <AppCard>
        <AppStackBetween>
            <h2>Categories list</h2>
            <AppButton
                variant="muted"
                type="button"
                :disabled="isLoading"
                @click="$emit('refresh')"
            >
                Refresh
            </AppButton>
        </AppStackBetween>

        <AdminCategoriesFiltersBar
            v-model:search-query="searchQuery"
            v-model:status-filter="statusFilter"
            :is-loading="isLoading"
        />

        <AdminCategoriesTable
            :categories="categories"
            :is-loading="isLoading"
            :is-deleting-id="isDeletingId"
            :can-delete-categories="canDeleteCategories"
            @edit="$emit('edit', $event)"
            @remove="$emit('remove', $event)"
        />

        <AdminCategoriesPaginationBar
            :page="page"
            :meta="meta"
            :is-loading="isLoading"
            @load-prev="$emit('loadPrev')"
            @load-next="$emit('loadNext')"
        />
    </AppCard>
</template>

<script setup lang="ts">
import type { AdminCategory, CategoryStatusFilter } from "@/types/admin-categories";
import type { PaginationMeta } from "@/types/pagination";
import AppButton from "@/components/ui/actions/AppButton.vue";
import AppCard from "@/components/ui/layout/AppCard.vue";
import AppStackBetween from "@/components/ui/actions/AppStackBetween.vue";

import AdminCategoriesFiltersBar from "./AdminCategoriesFiltersBar.vue";
import AdminCategoriesPaginationBar from "./AdminCategoriesPaginationBar.vue";
import AdminCategoriesTable from "./AdminCategoriesTable.vue";

defineProps<{
    categories: AdminCategory[];
    isLoading: boolean;
    page: number;
    meta: PaginationMeta;
    isDeletingId: number | null;
    canDeleteCategories: boolean;
}>();

defineEmits<{
    (event: "refresh"): void;
    (event: "edit", category: AdminCategory): void;
    (event: "remove", category: AdminCategory): void;
    (event: "loadPrev"): void;
    (event: "loadNext"): void;
}>();

const searchQuery = defineModel<string>("searchQuery", { required: true });
const statusFilter = defineModel<CategoryStatusFilter>("statusFilter", { required: true });
</script>
