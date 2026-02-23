<template>
    <AppStackBetween>
        <AppSectionTitle tag="h1">{{ isLoginMode ? "Login" : "Register" }}</AppSectionTitle>
        <AppButton variant="muted" type="button" @click="$emit('toggle')">
            {{ isLoginMode ? "Need account?" : "Have account?" }}
        </AppButton>
    </AppStackBetween>
</template>

<script setup lang="ts">
import AppButton from "@/components/ui/AppButton.vue";
import AppSectionTitle from "@/components/ui/AppSectionTitle.vue";
import AppStackBetween from "@/components/ui/AppStackBetween.vue";

defineProps<{
    isLoginMode: boolean;
}>();

defineEmits<{
    (event: "toggle"): void;
}>();
</script>
