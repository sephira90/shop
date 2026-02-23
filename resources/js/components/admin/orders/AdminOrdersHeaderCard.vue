<template>
    <AppStackBetween>
        <div>
            <AppSectionTitle tag="h1">Admin orders</AppSectionTitle>
            <AppMutedText
                >Monitor order lifecycle and update statuses from one workspace.</AppMutedText
            >
        </div>
        <AppButton variant="muted" type="button" :disabled="isLoading" @click="$emit('refresh')">
            {{ isLoading ? "Refreshing..." : "Refresh list" }}
        </AppButton>
    </AppStackBetween>
</template>

<script setup lang="ts">
import AppButton from "@/components/ui/actions/AppButton.vue";
import AppMutedText from "@/components/ui/typography/AppMutedText.vue";
import AppSectionTitle from "@/components/ui/typography/AppSectionTitle.vue";
import AppStackBetween from "@/components/ui/actions/AppStackBetween.vue";

defineProps<{
    isLoading: boolean;
}>();

defineEmits<{
    (event: "refresh"): void;
}>();
</script>
