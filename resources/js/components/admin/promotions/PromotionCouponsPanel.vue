<template>
    <AppCard v-if="selectedPromotion">
        <AppSectionTitle>Coupons for {{ selectedPromotion.name }}</AppSectionTitle>
        <AppMutedText
            >Create and manage checkout-ready coupon codes for this campaign.</AppMutedText
        >

        <AppFormShell @submit="$emit('submit')">
            <AppGridTwoColumns>
                <AppFormField label="Coupon code">
                    <AppTextInput
                        v-model="couponForm.code"
                        maxlength="40"
                        placeholder="SAVE10"
                        required
                    />
                </AppFormField>
                <AppFormField label="Max redemptions">
                    <AppNumberInput v-model="couponForm.max_redemptions" min="1" />
                </AppFormField>
                <AppFormField label="Expires at">
                    <AppDateTimeInput v-model="couponForm.expires_at" />
                </AppFormField>
            </AppGridTwoColumns>

            <AppCheckboxField>
                <AppCheckboxInput v-model="couponForm.is_active" />
                <span>Coupon is active</span>
            </AppCheckboxField>

            <AppActionsRow>
                <AppButton variant="primary" type="submit" :disabled="isSubmittingCoupon">
                    {{ isSubmittingCoupon ? "Adding..." : "Add coupon" }}
                </AppButton>
            </AppActionsRow>
        </AppFormShell>

        <AppTableSection with-top-spacing>
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Status</th>
                    <th>Redeemed</th>
                    <th>Max</th>
                    <th>Expires</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody v-if="selectedPromotion.coupons.length">
                <tr v-for="coupon in selectedPromotion.coupons" :key="coupon.id">
                    <td>{{ coupon.code }}</td>
                    <td>
                        <BooleanStatusChip :value="coupon.is_active" />
                    </td>
                    <td>{{ coupon.redeemed_count }}</td>
                    <td>{{ coupon.max_redemptions ?? "-" }}</td>
                    <td>{{ formatDateTime(coupon.expires_at) }}</td>
                    <AppTableActionsCell>
                        <AppButton
                            variant="muted"
                            type="button"
                            :disabled="updatingCouponId === coupon.id"
                            @click="$emit('toggle', coupon)"
                        >
                            {{
                                updatingCouponId === coupon.id
                                    ? "Applying..."
                                    : coupon.is_active
                                      ? "Disable"
                                      : "Enable"
                            }}
                        </AppButton>
                    </AppTableActionsCell>
                </tr>
            </tbody>
            <tbody v-else>
                <AppTableEmptyStateRow :colspan="6" message="No coupons yet for this campaign." />
            </tbody>
        </AppTableSection>
    </AppCard>
</template>

<script setup lang="ts">
import AppActionsRow from "@/components/ui/actions/AppActionsRow.vue";
import AppButton from "@/components/ui/actions/AppButton.vue";
import AppCard from "@/components/ui/layout/AppCard.vue";
import AppCheckboxField from "@/components/ui/forms/AppCheckboxField.vue";
import AppCheckboxInput from "@/components/ui/forms/AppCheckboxInput.vue";
import AppDateTimeInput from "@/components/ui/forms/AppDateTimeInput.vue";
import AppFormField from "@/components/ui/forms/AppFormField.vue";
import AppFormShell from "@/components/ui/forms/AppFormShell.vue";
import AppGridTwoColumns from "@/components/ui/layout/AppGridTwoColumns.vue";
import AppMutedText from "@/components/ui/typography/AppMutedText.vue";
import AppNumberInput from "@/components/ui/forms/AppNumberInput.vue";
import AppSectionTitle from "@/components/ui/typography/AppSectionTitle.vue";
import AppTableSection from "@/components/ui/table/AppTableSection.vue";
import AppTableActionsCell from "@/components/ui/table/AppTableActionsCell.vue";
import AppTableEmptyStateRow from "@/components/ui/table/AppTableEmptyStateRow.vue";
import AppTextInput from "@/components/ui/forms/AppTextInput.vue";
import BooleanStatusChip from "@/components/ui/data-display/BooleanStatusChip.vue";
import { formatDateTime } from "@/utils/datetime";
import type { Coupon, CouponFormState, Promotion } from "@/types/admin-promotions";

defineProps<{
    selectedPromotion: Promotion | null;
    isSubmittingCoupon: boolean;
    updatingCouponId: number | null;
}>();

defineEmits<{
    (event: "submit"): void;
    (event: "toggle", coupon: Coupon): void;
}>();

const couponForm = defineModel<CouponFormState>("couponForm", { required: true });
</script>
