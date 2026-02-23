<template>
    <AppTableSection :with-top-spacing="withTopSpacing">
        <thead>
            <tr>
                <th>Item</th>
                <th>Qty</th>
                <th>Unit</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <tr v-for="(item, index) in items" :key="`${itemKeyPrefix}-${item.sku}-${index}`">
                <td>
                    <strong>{{ item.name }}</strong>
                    <AppMutedText v-if="item.sku">{{ item.sku }}</AppMutedText>
                </td>
                <td>{{ item.quantity }}</td>
                <td>{{ formatPrice(item.unit_price, currency) }}</td>
                <td>{{ formatPrice(item.total_price, currency) }}</td>
            </tr>
        </tbody>
    </AppTableSection>
</template>

<script setup lang="ts">
import AppMutedText from "@/components/ui/AppMutedText.vue";
import AppTableSection from "@/components/ui/AppTableSection.vue";

export interface OrderItemsTableRow {
    name: string;
    sku: string;
    quantity: number;
    unit_price: number;
    total_price: number;
}

withDefaults(
    defineProps<{
        items: OrderItemsTableRow[];
        formatPrice: (value: number, currency?: string) => string;
        itemKeyPrefix: string;
        currency?: string;
        withTopSpacing?: boolean;
    }>(),
    {
        currency: undefined,
        withTopSpacing: false,
    },
);
</script>
