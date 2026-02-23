<template>
    <AppFormShell @submit="$emit('submit')">
        <AppGridTwoColumns>
            <AppTextInput v-model="firstNameModel" placeholder="First name" required />
            <AppTextInput v-model="lastNameModel" placeholder="Last name" required />
        </AppGridTwoColumns>
        <AppTextInput v-model="emailModel" type="email" placeholder="Email" required />
        <AppTextInput v-model="passwordModel" type="password" placeholder="Password" required />
        <AppTextInput
            v-model="passwordConfirmationModel"
            type="password"
            placeholder="Confirm password"
            required
        />
        <AppButton variant="primary" type="submit" :disabled="isSubmitting">
            {{ isSubmitting ? "Creating..." : "Create account" }}
        </AppButton>
    </AppFormShell>
</template>

<script setup lang="ts">
import { computed } from "vue";
import AppButton from "@/components/ui/actions/AppButton.vue";
import AppFormShell from "@/components/ui/forms/AppFormShell.vue";
import AppGridTwoColumns from "@/components/ui/layout/AppGridTwoColumns.vue";
import AppTextInput from "@/components/ui/forms/AppTextInput.vue";

const props = defineProps<{
    firstName: string;
    lastName: string;
    email: string;
    password: string;
    passwordConfirmation: string;
    isSubmitting: boolean;
}>();

const emit = defineEmits<{
    (event: "update:firstName", value: string): void;
    (event: "update:lastName", value: string): void;
    (event: "update:email", value: string): void;
    (event: "update:password", value: string): void;
    (event: "update:passwordConfirmation", value: string): void;
    (event: "submit"): void;
}>();

const firstNameModel = computed({
    get: (): string => props.firstName,
    set: (value: string): void => emit("update:firstName", value),
});

const lastNameModel = computed({
    get: (): string => props.lastName,
    set: (value: string): void => emit("update:lastName", value),
});

const emailModel = computed({
    get: (): string => props.email,
    set: (value: string): void => emit("update:email", value),
});

const passwordModel = computed({
    get: (): string => props.password,
    set: (value: string): void => emit("update:password", value),
});

const passwordConfirmationModel = computed({
    get: (): string => props.passwordConfirmation,
    set: (value: string): void => emit("update:passwordConfirmation", value),
});
</script>
