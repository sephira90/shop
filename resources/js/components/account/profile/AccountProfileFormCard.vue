<template>
    <AppCard tag="article">
        <AppSectionTitle>Edit profile</AppSectionTitle>
        <AppMutedText>
            Update your personal details used for orders and account communication.
        </AppMutedText>

        <AppFormLayout with-top-spacing @submit="$emit('submit')">
            <AppGridTwoColumns>
                <AppFormField label="First name">
                    <AppTextInput v-model="form.first_name" maxlength="80" required />
                </AppFormField>
                <AppFormField label="Last name">
                    <AppTextInput v-model="form.last_name" maxlength="80" required />
                </AppFormField>
            </AppGridTwoColumns>
            <AppFormField label="Email">
                <AppTextInput :model-value="profileEmail" type="email" disabled readonly />
            </AppFormField>
            <AppFormField label="Phone">
                <AppTextInput v-model="form.phone" maxlength="32" placeholder="+15551234567" />
            </AppFormField>
            <AppSubmitResetActions>
                <template #primary>
                    <AppButton variant="primary" type="submit" :disabled="isSavingProfile">
                        {{ isSavingProfile ? "Saving..." : "Save profile" }}
                    </AppButton>
                </template>
                <template #secondary>
                    <AppButton
                        variant="muted"
                        type="button"
                        :disabled="isSavingProfile"
                        @click="$emit('reset')"
                    >
                        Reset
                    </AppButton>
                </template>
            </AppSubmitResetActions>
        </AppFormLayout>
        <AppNotice
            v-if="noticeMessage"
            :message="noticeMessage"
            :variant="noticeType === 'success' ? 'success' : 'error'"
        />
    </AppCard>
</template>

<script setup lang="ts">
import AppButton from "@/components/ui/AppButton.vue";
import AppCard from "@/components/ui/AppCard.vue";
import AppFormField from "@/components/ui/AppFormField.vue";
import AppFormLayout from "@/components/ui/AppFormLayout.vue";
import AppGridTwoColumns from "@/components/ui/AppGridTwoColumns.vue";
import AppMutedText from "@/components/ui/AppMutedText.vue";
import AppNotice from "@/components/ui/AppNotice.vue";
import AppSectionTitle from "@/components/ui/AppSectionTitle.vue";
import AppSubmitResetActions from "@/components/ui/AppSubmitResetActions.vue";
import AppTextInput from "@/components/ui/AppTextInput.vue";

interface AccountProfileFormState {
    first_name: string;
    last_name: string;
    phone: string;
}

defineProps<{
    isSavingProfile: boolean;
    profileEmail: string;
    noticeType: "success" | "error";
    noticeMessage: string;
}>();

defineEmits<{
    (event: "submit"): void;
    (event: "reset"): void;
}>();

const form = defineModel<AccountProfileFormState>("form", { required: true });
</script>
