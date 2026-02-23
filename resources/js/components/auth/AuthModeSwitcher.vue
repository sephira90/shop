<template>
    <AppStackBetween>
        <AppSectionTitle tag="h1">{{ isLoginMode ? "Login" : "Register" }}</AppSectionTitle>
        <AppButton variant="muted" type="button" @click="$emit('toggle')">
            {{ isLoginMode ? "Need account?" : "Have account?" }}
        </AppButton>
    </AppStackBetween>
</template>

<script setup lang="ts">
import AppButton from "@/components/ui/actions/AppButton.vue";
import AppSectionTitle from "@/components/ui/typography/AppSectionTitle.vue";
import AppStackBetween from "@/components/ui/actions/AppStackBetween.vue";

defineProps<{
    isLoginMode: boolean;
}>();

defineEmits<{
    (event: "toggle"): void;
}>();
</script>
