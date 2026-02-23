<template>
    <div class="account-hero">
        <div class="account-hero__main">
            <span class="pill">Account center</span>
            <h1>{{ profileName }}</h1>
            <p>
                Manage profile details, monitor order activity and quickly access account actions.
            </p>
        </div>

        <div class="account-hero__user">
            <div class="account-avatar" aria-hidden="true">{{ profileInitial }}</div>
            <div class="account-hero__meta">
                <p class="account-hero__email">{{ profileEmail }}</p>
                <AppActionsRow>
                    <AppStatusChip :label="verificationLabel" :tone="verificationTone" />
                    <AppStatusChip
                        v-for="role in roleLabels"
                        :key="role"
                        :label="role"
                        tone="role"
                    />
                </AppActionsRow>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import AppActionsRow from "@/components/ui/actions/AppActionsRow.vue";
import AppStatusChip from "@/components/ui/data-display/AppStatusChip.vue";
import type { StatusTone } from "@/utils/order-presentation";

defineProps<{
    profileName: string;
    profileInitial: string;
    profileEmail: string;
    verificationLabel: string;
    verificationTone: StatusTone;
    roleLabels: string[];
}>();
</script>
