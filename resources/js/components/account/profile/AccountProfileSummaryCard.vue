<template>
    <AppCard tag="article">
        <AppSectionTitle>Profile summary</AppSectionTitle>
        <dl class="profile-list">
            <div class="profile-list__row">
                <dt>Full name</dt>
                <dd>{{ profileName }}</dd>
            </div>
            <div class="profile-list__row">
                <dt>Email</dt>
                <dd>{{ profileEmail }}</dd>
            </div>
            <div class="profile-list__row">
                <dt>Phone</dt>
                <dd>{{ profilePhone }}</dd>
            </div>
            <div class="profile-list__row">
                <dt>Roles</dt>
                <dd>{{ roleLabels.join(", ") }}</dd>
            </div>
        </dl>

        <AppSectionTitle>Quick actions</AppSectionTitle>
        <AppActionsRow with-top-spacing>
            <AppButton variant="primary" to="/account/orders"> Open orders </AppButton>
            <AppButton variant="muted" to="/catalog">Go to catalog</AppButton>
            <AppButton v-if="canAccessAdmin" variant="muted" to="/admin"> Open admin </AppButton>
        </AppActionsRow>
        <AppMutedText class="actions--top">
            Keep your account data up to date and review order statuses in real time.
        </AppMutedText>
    </AppCard>
</template>

<script setup lang="ts">
import AppActionsRow from "@/components/ui/actions/AppActionsRow.vue";
import AppButton from "@/components/ui/actions/AppButton.vue";
import AppCard from "@/components/ui/layout/AppCard.vue";
import AppMutedText from "@/components/ui/typography/AppMutedText.vue";
import AppSectionTitle from "@/components/ui/typography/AppSectionTitle.vue";

defineProps<{
    profileName: string;
    profileEmail: string;
    profilePhone: string;
    roleLabels: string[];
    canAccessAdmin: boolean;
}>();
</script>
