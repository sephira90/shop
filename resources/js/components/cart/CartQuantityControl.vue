<template>
    <AppActionsRow>
        <AppButton
            data-testid="cart-qty-decrease"
            variant="muted"
            type="button"
            @click="$emit('decrease')"
        >
            -
        </AppButton>

        <AppQuantityInput
            data-testid="cart-qty-input"
            :min="min"
            :max="max"
            :model-value="quantity"
            aria-label="Quantity"
            style="width: 5rem"
            @update:model-value="$emit('updateQuantity', $event)"
        />

        <AppButton
            data-testid="cart-qty-increase"
            variant="muted"
            type="button"
            @click="$emit('increase')"
        >
            +
        </AppButton>
    </AppActionsRow>
</template>

<script setup lang="ts">
import AppActionsRow from "@/components/ui/AppActionsRow.vue";
import AppButton from "@/components/ui/AppButton.vue";
import AppQuantityInput from "@/components/ui/AppQuantityInput.vue";

withDefaults(
    defineProps<{
        quantity: number;
        min?: number;
        max?: number;
    }>(),
    {
        min: 1,
        max: 1000,
    },
);

defineEmits<{
    (event: "decrease"): void;
    (event: "increase"): void;
    (event: "updateQuantity", quantity: number): void;
}>();
</script>
