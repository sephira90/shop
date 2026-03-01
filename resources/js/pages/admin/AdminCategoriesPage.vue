<template>
    <section class="grid">
        <AdminCategoriesFormCard
            v-model:form="form"
            :parent-options="parentOptions"
            :is-loading-parent-options="isLoadingParentOptions"
            :is-submitting="isSubmitting"
            :editing-id="editingId"
            :notice-type="notice.type"
            :notice-message="notice.message"
            @reset-form="resetForm"
            @submit-category="submitCategory"
        />

        <AdminCategoriesListCard
            v-model:search-query="searchQuery"
            v-model:status-filter="statusFilter"
            :categories="filteredCategories"
            :is-loading="isLoading"
            :page="page"
            :meta="meta"
            :is-deleting-id="isDeletingId"
            :can-delete-categories="canDeleteCategories"
            @refresh="loadCategoriesPage(page)"
            @edit="startEdit"
            @remove="removeCategory"
            @load-prev="loadCategoriesPage(page - 1)"
            @load-next="loadCategoriesPage(page + 1)"
        />
    </section>
</template>

<script setup lang="ts">
import { onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";

import AdminCategoriesFormCard from "@/components/admin/categories/AdminCategoriesFormCard.vue";
import AdminCategoriesListCard from "@/components/admin/categories/AdminCategoriesListCard.vue";
import { createBrowserAdminUiEffects } from "@/composables/admin/adminUiEffects";
import { useAdminCategories } from "@/composables/admin/useAdminCategories";

const uiEffects = createBrowserAdminUiEffects();
const route = useRoute();
const router = useRouter();

const {
    page,
    isLoading,
    isSubmitting,
    isDeletingId,
    editingId,
    searchQuery,
    statusFilter,
    canDeleteCategories,
    meta,
    notice,
    form,
    filteredCategories,
    parentOptions,
    isLoadingParentOptions,
    resetForm,
    loadCategories,
    loadParentOptions,
    submitCategory,
    startEdit,
    removeCategory,
} = useAdminCategories({
    uiEffects,
    routeSync: {
        route,
        router,
    },
});

const loadCategoriesPage = async (targetPage?: number): Promise<void> => {
    await Promise.all([loadCategories(targetPage), loadParentOptions()]);
};

onMounted(async () => {
    await loadCategoriesPage();
});
</script>
