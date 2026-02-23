<template>
    <div class="grid">
        <AppTextInput v-model="emailModel" type="email" placeholder="Email" required />
        <AppTextInput v-model="couponCodeModel" placeholder="Coupon code (optional)" />
    </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import AppTextInput from "@/components/ui/AppTextInput.vue";

const props = defineProps<{
    email: string;
    couponCode: string;
}>();

const emit = defineEmits<{
    (event: "update:email", value: string): void;
    (event: "update:couponCode", value: string): void;
}>();

const emailModel = computed({
    get: (): string => props.email,
    set: (value: string): void => emit("update:email", value),
});

const couponCodeModel = computed({
    get: (): string => props.couponCode,
    set: (value: string): void => emit("update:couponCode", value),
});
</script>
