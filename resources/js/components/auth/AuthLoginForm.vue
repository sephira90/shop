<template>
    <AppFormShell @submit="$emit('submit')">
        <AppTextInput v-model="emailModel" type="email" placeholder="Email" required />
        <AppTextInput v-model="passwordModel" type="password" placeholder="Password" required />
        <AppButton variant="primary" type="submit" :disabled="isSubmitting">
            {{ isSubmitting ? "Signing in..." : "Sign in" }}
        </AppButton>
    </AppFormShell>
</template>

<script setup lang="ts">
import { computed } from "vue";
import AppButton from "@/components/ui/actions/AppButton.vue";
import AppFormShell from "@/components/ui/forms/AppFormShell.vue";
import AppTextInput from "@/components/ui/forms/AppTextInput.vue";

const props = defineProps<{
    email: string;
    password: string;
    isSubmitting: boolean;
}>();

const emit = defineEmits<{
    (event: "update:email", value: string): void;
    (event: "update:password", value: string): void;
    (event: "submit"): void;
}>();

const emailModel = computed({
    get: (): string => props.email,
    set: (value: string): void => emit("update:email", value),
});

const passwordModel = computed({
    get: (): string => props.password,
    set: (value: string): void => emit("update:password", value),
});
</script>
