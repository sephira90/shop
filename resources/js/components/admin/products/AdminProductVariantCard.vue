<template>
    <div class="variant-card">
        <div class="variant-card__header">
            <strong>Variant #{{ index + 1 }}</strong>
            <AppButton variant="muted" type="button" :disabled="!canRemove" @click="remove">
                Remove
            </AppButton>
        </div>

        <AppGridTwoColumns>
            <AppFormField label="Variant SKU">
                <AppTextInput v-model="variant.sku" placeholder="SKU-0001-BLACK-M" required />
            </AppFormField>

            <AppFormField label="Variant name">
                <AppTextInput v-model="variant.name" placeholder="Black / M" required />
            </AppFormField>

            <AppFormField label="Price">
                <AppNumberInput v-model="variant.price" min="0.01" step="0.01" required />
            </AppFormField>

            <AppFormField label="Compare at price">
                <AppNumberInput v-model="variant.compare_at_price" min="0.01" step="0.01" />
            </AppFormField>

            <AppFormField label="Currency">
                <AppTextInput v-model="variant.currency" maxlength="3" placeholder="USD" required />
            </AppFormField>
        </AppGridTwoColumns>

        <AppCheckboxField>
            <AppCheckboxInput v-model="variant.is_active" />
            <span>Variant is active</span>
        </AppCheckboxField>

        <AppFormField label="Attributes (JSON object)">
            <AppTextareaInput
                v-model="variant.attributes_json"
                class="variant-attributes"
                rows="3"
                placeholder='{"size":"M","color":"black"}'
            />
        </AppFormField>

        <AppGridThreeColumns>
            <AppFormField label="Inventory quantity">
                <AppNumberInput v-model="variant.inventory_quantity" min="0" step="1" required />
            </AppFormField>

            <AppFormField label="Reserved quantity">
                <AppNumberInput
                    v-model="variant.inventory_reserved_quantity"
                    min="0"
                    step="1"
                    required
                />
            </AppFormField>

            <AppFormField label="Low stock threshold">
                <AppNumberInput
                    v-model="variant.inventory_low_stock_threshold"
                    min="0"
                    step="1"
                    required
                />
            </AppFormField>
        </AppGridThreeColumns>
    </div>
</template>

<script setup lang="ts">
import AppButton from "@/components/ui/actions/AppButton.vue";
import AppCheckboxField from "@/components/ui/forms/AppCheckboxField.vue";
import AppCheckboxInput from "@/components/ui/forms/AppCheckboxInput.vue";
import AppFormField from "@/components/ui/forms/AppFormField.vue";
import AppGridThreeColumns from "@/components/ui/layout/AppGridThreeColumns.vue";
import AppGridTwoColumns from "@/components/ui/layout/AppGridTwoColumns.vue";
import AppNumberInput from "@/components/ui/forms/AppNumberInput.vue";
import AppTextareaInput from "@/components/ui/forms/AppTextareaInput.vue";
import AppTextInput from "@/components/ui/forms/AppTextInput.vue";
import type { ProductVariantForm } from "@/types/admin-products";

defineProps<{
    index: number;
    canRemove: boolean;
}>();

const emit = defineEmits<{
    (event: "remove"): void;
}>();

const variant = defineModel<ProductVariantForm>("variant", { required: true });

const remove = (): void => {
    emit("remove");
};
</script>
