<template>
    <section class="grid">
        <AccountHeroCard
            :profile-name="profileName"
            :profile-initial="profileInitial"
            :profile-email="profileEmail"
            :verification-label="verificationLabel"
            :verification-tone="verificationTone"
            :role-labels="roleLabels"
        />

        <AccountTabsNav />

        <AccountMetricsGrid :metrics="metrics" :format-price="formatPrice" />

        <AppGridTwoColumns>
            <AccountProfileFormCard
                v-model:form="form"
                :is-saving-profile="isSavingProfile"
                :profile-email="profileEmail"
                :notice-type="profileNotice.type"
                :notice-message="profileNotice.message"
                @submit="submitProfileUpdate"
                @reset="resetProfileForm"
            />

            <AccountProfileSummaryCard
                :profile-name="profileName"
                :profile-email="profileEmail"
                :profile-phone="profilePhone"
                :role-labels="roleLabels"
                :can-access-admin="authStore.canAccessAdmin"
            />
        </AppGridTwoColumns>
    </section>
</template>

<script setup lang="ts">
import { onMounted } from "vue";

import AccountTabsNav from "@/components/account/AccountTabsNav.vue";
import AccountHeroCard from "@/components/account/profile/AccountHeroCard.vue";
import AccountMetricsGrid from "@/components/account/profile/AccountMetricsGrid.vue";
import AccountProfileFormCard from "@/components/account/profile/AccountProfileFormCard.vue";
import AccountProfileSummaryCard from "@/components/account/profile/AccountProfileSummaryCard.vue";
import AppGridTwoColumns from "@/components/ui/AppGridTwoColumns.vue";
import { useAccountProfile } from "@/composables/useAccountProfile";

const {
    authStore,
    isSavingProfile,
    metrics,
    form,
    profileNotice,
    profileName,
    profileEmail,
    profilePhone,
    profileInitial,
    verificationLabel,
    verificationTone,
    roleLabels,
    resetProfileForm,
    formatPrice,
    submitProfileUpdate,
    loadProfile,
} = useAccountProfile();

onMounted(async () => {
    await loadProfile();
});
</script>
