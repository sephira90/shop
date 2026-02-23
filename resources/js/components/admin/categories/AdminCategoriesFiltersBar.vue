<template>
    <AppActionsRow with-top-spacing>
        <AppSearchInput
            v-model="searchQuery"
            placeholder="Filter by name, slug or parent"
            :disabled="isLoading"
        />
        <AppFilterSelect v-model="statusFilter" :disabled="isLoading">
            <option value="all">All statuses</option>
            <option value="active">Only active</option>
            <option value="inactive">Only inactive</option>
        </AppFilterSelect>
    </AppActionsRow>
</template>

<script setup lang="ts">
import AppActionsRow from "@/components/ui/AppActionsRow.vue";
import AppFilterSelect from "@/components/ui/AppFilterSelect.vue";
import AppSearchInput from "@/components/ui/AppSearchInput.vue";
import type { CategoryStatusFilter } from "@/types/admin-categories";

defineProps<{
    isLoading: boolean;
}>();

const searchQuery = defineModel<string>("searchQuery", { required: true });
const statusFilter = defineModel<CategoryStatusFilter>("statusFilter", { required: true });
</script>
