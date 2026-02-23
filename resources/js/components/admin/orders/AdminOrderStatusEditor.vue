<template>
    <AppGridThreeColumns with-top-spacing>
        <AppFormField label="Order status">
            <AppEnumSelect v-model="draft.status" :options="orderStatusOptions" />
        </AppFormField>
        <AppFormField label="Payment status">
            <AppEnumSelect v-model="draft.payment_status" :options="paymentStatusOptions" />
        </AppFormField>
        <AppFormField label="Shipment status">
            <AppEnumSelect v-model="draft.shipment_status" :options="shipmentStatusOptions" />
        </AppFormField>
    </AppGridThreeColumns>

    <AppActionsRow with-top-spacing>
        <AppButton variant="primary" type="button" :disabled="draft.saving" @click="$emit('save')">
            {{ draft.saving ? "Saving..." : "Save statuses" }}
        </AppButton>
    </AppActionsRow>
</template>

<script setup lang="ts">
import AppActionsRow from "@/components/ui/actions/AppActionsRow.vue";
import AppButton from "@/components/ui/actions/AppButton.vue";
import AppEnumSelect from "@/components/ui/forms/AppEnumSelect.vue";
import AppFormField from "@/components/ui/forms/AppFormField.vue";
import AppGridThreeColumns from "@/components/ui/layout/AppGridThreeColumns.vue";
import type { StatusDraft } from "@/composables/admin/orders/useAdminOrdersQuery";

const draft = defineModel<StatusDraft>("draft", { required: true });

const orderStatusOptions = [
    { value: "pending", label: "Pending" },
    { value: "paid", label: "Paid" },
    { value: "processing", label: "Processing" },
    { value: "shipped", label: "Shipped" },
    { value: "completed", label: "Completed" },
    { value: "cancelled", label: "Cancelled" },
    { value: "refunded", label: "Refunded" },
];

const paymentStatusOptions = [
    { value: "pending", label: "Pending" },
    { value: "authorized", label: "Authorized" },
    { value: "captured", label: "Captured" },
    { value: "failed", label: "Failed" },
    { value: "refunded", label: "Refunded" },
];

const shipmentStatusOptions = [
    { value: "pending", label: "Pending" },
    { value: "packed", label: "Packed" },
    { value: "shipped", label: "Shipped" },
    { value: "delivered", label: "Delivered" },
    { value: "returned", label: "Returned" },
];

defineEmits<{
    (event: "save"): void;
}>();
</script>
