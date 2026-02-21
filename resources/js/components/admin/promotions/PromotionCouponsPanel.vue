<template>
    <div class="card" v-if="selectedPromotion">
        <h2 class="section-title">Coupons for {{ selectedPromotion.name }}</h2>
        <p class="muted">Create and manage checkout-ready coupon codes for this campaign.</p>

        <form class="grid actions--top" @submit.prevent="$emit('submit')">
            <div class="grid grid-2">
                <label class="field">
                    <span class="field__label">Coupon code</span>
                    <input v-model="couponForm.code" maxlength="40" placeholder="SAVE10" required />
                </label>
                <label class="field">
                    <span class="field__label">Max redemptions</span>
                    <input v-model="couponForm.max_redemptions" type="number" min="1" />
                </label>
                <label class="field">
                    <span class="field__label">Expires at</span>
                    <input v-model="couponForm.expires_at" type="datetime-local" />
                </label>
            </div>

            <label class="checkbox-field">
                <input v-model="couponForm.is_active" type="checkbox" />
                <span>Coupon is active</span>
            </label>

            <div class="actions">
                <button class="btn btn-primary" type="submit" :disabled="isSubmittingCoupon">
                    {{ isSubmittingCoupon ? "Adding..." : "Add coupon" }}
                </button>
            </div>
        </form>

        <div class="table-wrap actions--top">
            <table class="table">
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
                            <span
                                class="status-chip"
                                :class="
                                    coupon.is_active ? 'status-chip--good' : 'status-chip--neutral'
                                "
                            >
                                {{ coupon.is_active ? "active" : "inactive" }}
                            </span>
                        </td>
                        <td>{{ coupon.redeemed_count }}</td>
                        <td>{{ coupon.max_redemptions ?? "-" }}</td>
                        <td>{{ formatDateTime(coupon.expires_at) }}</td>
                        <td>
                            <button
                                class="btn btn-muted"
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
                            </button>
                        </td>
                    </tr>
                </tbody>
                <tbody v-else>
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <p>No coupons yet for this campaign.</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script setup lang="ts">
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
