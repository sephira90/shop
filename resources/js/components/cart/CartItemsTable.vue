<template>
    <div>
        <AppTableSection>
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Qty</th>
                    <th>Price</th>
                    <th>Total</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="item in items" :key="item.product_variant_id">
                    <td>{{ item.name }}</td>
                    <td>
                        <CartQuantityControl
                            :quantity="item.quantity"
                            @decrease="$emit('decreaseQuantity', item)"
                            @increase="$emit('increaseQuantity', item)"
                            @update-quantity="
                                (quantity) => $emit('updateQuantity', { item, quantity })
                            "
                        />
                    </td>
                    <td>{{ formatPrice(item.unit_price) }}</td>
                    <td>{{ formatPrice(item.line_total) }}</td>
                    <td>
                        <AppButton
                            data-testid="cart-remove-item"
                            variant="muted"
                            type="button"
                            @click="$emit('removeItem', item.product_variant_id)"
                        >
                            Remove
                        </AppButton>
                    </td>
                </tr>
            </tbody>
        </AppTableSection>

        <AppActionsRow with-top-spacing>
            <AppButton variant="primary" to="/checkout"> Proceed to checkout </AppButton>
        </AppActionsRow>
    </div>
</template>

<script setup lang="ts">
import AppActionsRow from "@/components/ui/actions/AppActionsRow.vue";
import AppButton from "@/components/ui/actions/AppButton.vue";
import type { CartItem } from "@/stores/cart";

import AppTableSection from "@/components/ui/table/AppTableSection.vue";
import CartQuantityControl from "./CartQuantityControl.vue";

defineProps<{
    items: CartItem[];
}>();

defineEmits<{
    (event: "removeItem", variantId: number): void;
    (event: "increaseQuantity", item: CartItem): void;
    (event: "decreaseQuantity", item: CartItem): void;
    (event: "updateQuantity", payload: { item: CartItem; quantity: number }): void;
}>();

const formatPrice = (value: number): string => {
    return Number(value).toFixed(2);
};
</script>
