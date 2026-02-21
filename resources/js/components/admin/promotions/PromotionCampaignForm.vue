<template>
    <div class="card">
        <div class="stack stack--between">
            <div>
                <h1 class="section-title">Admin promotions</h1>
                <p class="muted">Create campaigns and manage coupon codes used in checkout.</p>
            </div>
            <div class="actions">
                <button class="btn btn-muted" type="button" :disabled="isLoading" @click="$emit('refresh')">
                    {{ isLoading ? 'Refreshing...' : 'Refresh' }}
                </button>
                <button class="btn btn-muted" type="button" :disabled="isSubmittingPromotion" @click="$emit('reset')">
                    {{ editingPromotionId ? 'Cancel editing' : 'New campaign' }}
                </button>
            </div>
        </div>

        <form class="form-grid actions--top" @submit.prevent="$emit('submit')">
            <div class="grid grid-2">
                <label class="field">
                    <span class="field__label">Campaign name</span>
                    <input v-model="promotionForm.name" placeholder="Weekend deal" required />
                </label>
                <label class="field">
                    <span class="field__label">Discount type</span>
                    <select v-model="promotionForm.type">
                        <option value="percent">Percent</option>
                        <option value="fixed">Fixed</option>
                    </select>
                </label>
                <label class="field">
                    <span class="field__label">Discount value</span>
                    <input v-model.number="promotionForm.value" type="number" min="0.01" step="0.01" required />
                </label>
                <label class="field">
                    <span class="field__label">{{ editingPromotionId ? 'Campaign code (optional)' : 'Primary coupon code' }}</span>
                    <input v-model="promotionForm.code" maxlength="40" placeholder="TEST1" />
                </label>
                <label class="field">
                    <span class="field__label">Starts at</span>
                    <input v-model="promotionForm.starts_at" type="datetime-local" />
                </label>
                <label class="field">
                    <span class="field__label">Ends at</span>
                    <input v-model="promotionForm.ends_at" type="datetime-local" />
                </label>
                <label class="field">
                    <span class="field__label">Usage limit</span>
                    <input v-model="promotionForm.usage_limit" type="number" min="1" />
                </label>
                <label v-if="!editingPromotionId" class="field">
                    <span class="field__label">Primary coupon max redemptions</span>
                    <input v-model="promotionForm.coupon_max_redemptions" type="number" min="1" />
                </label>
                <label v-if="!editingPromotionId" class="field">
                    <span class="field__label">Primary coupon expires at</span>
                    <input v-model="promotionForm.coupon_expires_at" type="datetime-local" />
                </label>
            </div>

            <div class="actions">
                <label class="checkbox-field">
                    <input v-model="promotionForm.is_active" type="checkbox" />
                    <span>Campaign is active</span>
                </label>
                <label v-if="!editingPromotionId" class="checkbox-field">
                    <input v-model="promotionForm.coupon_is_active" type="checkbox" />
                    <span>Primary coupon is active</span>
                </label>
            </div>

            <div class="actions">
                <button class="btn btn-primary" type="submit" :disabled="isSubmittingPromotion">
                    {{
                        isSubmittingPromotion
                            ? editingPromotionId
                              ? 'Updating...'
                              : 'Creating...'
                            : editingPromotionId
                              ? 'Update campaign'
                              : 'Create campaign'
                    }}
                </button>
                <button class="btn btn-muted" type="button" :disabled="isSubmittingPromotion" @click="$emit('reset')">
                    Reset fields
                </button>
            </div>
        </form>

        <p v-if="noticeMessage" :class="['notice', noticeType === 'success' ? 'notice--success' : 'notice--error']">
            {{ noticeMessage }}
        </p>
    </div>
</template>

<script setup lang="ts">
import type { PromotionFormState, PromotionNotice } from '@/types/admin-promotions';

defineProps<{
    editingPromotionId: number | null;
    isLoading: boolean;
    isSubmittingPromotion: boolean;
    noticeType: PromotionNotice['type'];
    noticeMessage: string;
}>();

defineEmits<{
    (event: 'refresh'): void;
    (event: 'reset'): void;
    (event: 'submit'): void;
}>();

const promotionForm = defineModel<PromotionFormState>('promotionForm', { required: true });
</script>
