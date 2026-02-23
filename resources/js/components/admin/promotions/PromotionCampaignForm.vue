<template>
    <AppCard>
        <AppStackBetween>
            <div>
                <AppSectionTitle tag="h1">Admin promotions</AppSectionTitle>
                <AppMutedText
                    >Create campaigns and manage coupon codes used in checkout.</AppMutedText
                >
            </div>
            <AppActionsRow>
                <AppButton
                    variant="muted"
                    type="button"
                    :disabled="isLoading"
                    @click="$emit('refresh')"
                >
                    {{ isLoading ? "Refreshing..." : "Refresh" }}
                </AppButton>
                <AppButton
                    variant="muted"
                    type="button"
                    :disabled="isSubmittingPromotion"
                    @click="$emit('reset')"
                >
                    {{ editingPromotionId ? "Cancel editing" : "New campaign" }}
                </AppButton>
            </AppActionsRow>
        </AppStackBetween>

        <AppFormLayout with-top-spacing @submit="$emit('submit')">
            <AppGridTwoColumns>
                <AppFormField label="Campaign name">
                    <AppTextInput
                        v-model="promotionForm.name"
                        placeholder="Weekend deal"
                        required
                    />
                </AppFormField>
                <AppFormField label="Discount type">
                    <AppEnumSelect v-model="promotionForm.type" :options="promotionTypeOptions" />
                </AppFormField>
                <AppFormField label="Discount value">
                    <AppNumberInput
                        v-model.number="promotionForm.value"
                        min="0.01"
                        step="0.01"
                        required
                    />
                </AppFormField>
                <AppFormField
                    :label="editingPromotionId ? 'Campaign code (optional)' : 'Primary coupon code'"
                >
                    <AppTextInput v-model="promotionForm.code" maxlength="40" placeholder="TEST1" />
                </AppFormField>
                <AppFormField label="Starts at">
                    <AppDateTimeInput v-model="promotionForm.starts_at" />
                </AppFormField>
                <AppFormField label="Ends at">
                    <AppDateTimeInput v-model="promotionForm.ends_at" />
                </AppFormField>
                <AppFormField label="Usage limit">
                    <AppNumberInput v-model="promotionForm.usage_limit" min="1" />
                </AppFormField>
                <AppFormField v-if="!editingPromotionId" label="Primary coupon max redemptions">
                    <AppNumberInput v-model="promotionForm.coupon_max_redemptions" min="1" />
                </AppFormField>
                <AppFormField v-if="!editingPromotionId" label="Primary coupon expires at">
                    <AppDateTimeInput v-model="promotionForm.coupon_expires_at" />
                </AppFormField>
            </AppGridTwoColumns>

            <AppActionsRow>
                <AppCheckboxField>
                    <AppCheckboxInput v-model="promotionForm.is_active" />
                    <span>Campaign is active</span>
                </AppCheckboxField>
                <AppCheckboxField v-if="!editingPromotionId">
                    <AppCheckboxInput v-model="promotionForm.coupon_is_active" />
                    <span>Primary coupon is active</span>
                </AppCheckboxField>
            </AppActionsRow>

            <AppSubmitResetActions>
                <template #primary>
                    <AppButton variant="primary" type="submit" :disabled="isSubmittingPromotion">
                        {{
                            isSubmittingPromotion
                                ? editingPromotionId
                                    ? "Updating..."
                                    : "Creating..."
                                : editingPromotionId
                                  ? "Update campaign"
                                  : "Create campaign"
                        }}
                    </AppButton>
                </template>
                <template #secondary>
                    <AppButton
                        variant="muted"
                        type="button"
                        :disabled="isSubmittingPromotion"
                        @click="$emit('reset')"
                    >
                        Reset fields
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
import AppActionsRow from "@/components/ui/actions/AppActionsRow.vue";
import AppButton from "@/components/ui/actions/AppButton.vue";
import AppCard from "@/components/ui/layout/AppCard.vue";
import AppCheckboxField from "@/components/ui/forms/AppCheckboxField.vue";
import AppCheckboxInput from "@/components/ui/forms/AppCheckboxInput.vue";
import AppDateTimeInput from "@/components/ui/forms/AppDateTimeInput.vue";
import AppEnumSelect from "@/components/ui/forms/AppEnumSelect.vue";
import AppFormField from "@/components/ui/forms/AppFormField.vue";
import AppFormLayout from "@/components/ui/forms/AppFormLayout.vue";
import AppGridTwoColumns from "@/components/ui/layout/AppGridTwoColumns.vue";
import AppMutedText from "@/components/ui/typography/AppMutedText.vue";
import AppNotice from "@/components/ui/feedback/AppNotice.vue";
import AppNumberInput from "@/components/ui/forms/AppNumberInput.vue";
import AppSectionTitle from "@/components/ui/typography/AppSectionTitle.vue";
import AppStackBetween from "@/components/ui/actions/AppStackBetween.vue";
import AppSubmitResetActions from "@/components/ui/actions/AppSubmitResetActions.vue";
import AppTextInput from "@/components/ui/forms/AppTextInput.vue";
import type { PromotionFormState, PromotionNotice } from "@/types/admin-promotions";

defineProps<{
    editingPromotionId: number | null;
    isLoading: boolean;
    isSubmittingPromotion: boolean;
    noticeType: PromotionNotice["type"];
    noticeMessage: string;
}>();

defineEmits<{
    (event: "refresh"): void;
    (event: "reset"): void;
    (event: "submit"): void;
}>();

const promotionForm = defineModel<PromotionFormState>("promotionForm", { required: true });

const promotionTypeOptions = [
    { value: "percent", label: "Percent" },
    { value: "fixed", label: "Fixed" },
];
</script>
