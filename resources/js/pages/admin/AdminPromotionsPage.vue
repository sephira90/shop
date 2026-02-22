<template>
    <section class="grid">
        <PromotionCampaignForm
            v-model:promotion-form="promotionForm"
            :editing-promotion-id="editingPromotionId"
            :is-loading="isLoading"
            :is-submitting-promotion="isSubmittingPromotion"
            :notice-type="notice.type"
            :notice-message="notice.message"
            @refresh="loadPromotions(page)"
            @reset="resetPromotionForm"
            @submit="submitPromotion"
        />

        <PromotionCampaignTable
            v-model:search-query="searchQuery"
            v-model:status-filter="statusFilter"
            :promotions="filteredPromotions"
            :selected-promotion-id="selectedPromotionId"
            :is-loading="isLoading"
            :page="page"
            :meta="meta"
            :is-deleting-promotion-id="isDeletingPromotionId"
            @select="selectPromotion"
            @edit="startEditPromotion"
            @remove="removePromotion"
            @load-prev="loadPromotions(page - 1)"
            @load-next="loadPromotions(page + 1)"
        />

        <PromotionCouponsPanel
            v-model:coupon-form="couponForm"
            :selected-promotion="selectedPromotion"
            :is-submitting-coupon="isSubmittingCoupon"
            :updating-coupon-id="updatingCouponId"
            @submit="createCoupon"
            @toggle="toggleCoupon"
        />
    </section>
</template>

<script setup lang="ts">
import { onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";

import PromotionCampaignForm from "@/components/admin/promotions/PromotionCampaignForm.vue";
import PromotionCampaignTable from "@/components/admin/promotions/PromotionCampaignTable.vue";
import PromotionCouponsPanel from "@/components/admin/promotions/PromotionCouponsPanel.vue";
import { createBrowserAdminUiEffects } from "@/composables/admin/adminUiEffects";
import { useAdminPromotions } from "@/composables/admin/useAdminPromotions";

const uiEffects = createBrowserAdminUiEffects();
const route = useRoute();
const router = useRouter();

const {
    page,
    isLoading,
    isSubmittingPromotion,
    isSubmittingCoupon,
    isDeletingPromotionId,
    updatingCouponId,
    editingPromotionId,
    selectedPromotionId,
    searchQuery,
    statusFilter,
    meta,
    notice,
    promotionForm,
    couponForm,
    filteredPromotions,
    selectedPromotion,
    loadPromotions,
    selectPromotion,
    resetPromotionForm,
    startEditPromotion,
    submitPromotion,
    removePromotion,
    createCoupon,
    toggleCoupon,
} = useAdminPromotions({
    uiEffects,
    routeSync: {
        route,
        router,
    },
});

onMounted(async () => {
    await loadPromotions();
});
</script>
