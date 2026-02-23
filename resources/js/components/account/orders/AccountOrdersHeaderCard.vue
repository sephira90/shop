<template>
    <AppStackBetween>
        <div>
            <AppSectionTitle tag="h1">My orders</AppSectionTitle>
            <AppMutedText
                >Track statuses, payment and shipment updates for your purchases.</AppMutedText
            >
        </div>
        <AppButton variant="muted" type="button" :disabled="isLoading" @click="$emit('refresh')">
            {{ isLoading ? "Refreshing..." : "Refresh" }}
        </AppButton>
    </AppStackBetween>
</template>

<script setup lang="ts">
import AppButton from "@/components/ui/AppButton.vue";
import AppMutedText from "@/components/ui/AppMutedText.vue";
import AppSectionTitle from "@/components/ui/AppSectionTitle.vue";
import AppStackBetween from "@/components/ui/AppStackBetween.vue";

defineProps<{
    isLoading: boolean;
}>();

defineEmits<{
    (event: "refresh"): void;
}>();
</script>
