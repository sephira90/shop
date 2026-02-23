<template>
    <section class="grid">
        <AdminProductsFormCard
            v-model:form="form"
            :categories="categories"
            :is-loading-categories="isLoadingCategories"
            :is-submitting="isSubmitting"
            :is-refreshing-catalog-cache="isRefreshingCatalogCache"
            :editing-id="editingId"
            :notice-type="notice.type"
            :notice-message="notice.message"
            @refresh-catalog-cache="refreshCatalogCache"
            @reset-form="resetForm"
            @submit-product="submitProduct"
            @load-categories="loadCategories"
            @add-variant="addVariant"
            @remove-variant="removeVariant"
        />

        <AdminProductsListCard
            v-model:search-query="searchQuery"
            :products="filteredProducts"
            :is-loading="isLoading"
            :page="page"
            :meta="meta"
            :is-deleting-id="isDeletingId"
            :is-visibility-updating-id="isVisibilityUpdatingId"
            :can-delete-products="canDeleteProducts"
            :status-badge-tone="statusBadgeTone"
            :is-visible-in-catalog="isVisibleInCatalog"
            @refresh="loadProducts(page)"
            @edit="startEdit"
            @toggle-visibility="toggleCatalogVisibility"
            @remove="removeProduct"
            @load-prev="loadProducts(page - 1)"
            @load-next="loadProducts(page + 1)"
        />
    </section>
</template>

<script setup lang="ts">
import { onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";

import AdminProductsFormCard from "@/components/admin/products/AdminProductsFormCard.vue";
import AdminProductsListCard from "@/components/admin/products/AdminProductsListCard.vue";
import { createBrowserAdminUiEffects } from "@/composables/admin/adminUiEffects";
import { useAdminProducts } from "@/composables/admin/useAdminProducts";

const uiEffects = createBrowserAdminUiEffects();
const route = useRoute();
const router = useRouter();

const {
    categories,
    page,
    isLoading,
    isLoadingCategories,
    isSubmitting,
    isDeletingId,
    isVisibilityUpdatingId,
    isRefreshingCatalogCache,
    editingId,
    searchQuery,
    canDeleteProducts,
    meta,
    notice,
    form,
    filteredProducts,
    statusBadgeTone,
    resetForm,
    addVariant,
    removeVariant,
    loadCategories,
    loadProducts,
    submitProduct,
    startEdit,
    removeProduct,
    refreshCatalogCache,
    toggleCatalogVisibility,
    isVisibleInCatalog,
} = useAdminProducts({
    uiEffects,
    routeSync: {
        route,
        router,
    },
});

onMounted(async () => {
    await Promise.all([loadCategories(), loadProducts()]);
});
</script>
