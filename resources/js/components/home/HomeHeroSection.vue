<template>
    <div class="hero">
        <span class="pill">Production-ready storefront</span>
        <h1>Laravel + Vue ecommerce shell</h1>
        <p>
            A clean baseline for catalog, checkout and admin operations with one visual language
            across all pages.
        </p>
        <div class="hero__actions">
            <AppButton variant="primary" to="/catalog">Browse catalog</AppButton>
            <AppButton v-if="canAccessAdmin" variant="muted" to="/admin">
                Open admin area
            </AppButton>
            <AppButton v-else-if="canAccessAccount" variant="muted" to="/account/profile">
                Open account
            </AppButton>
            <AppButton v-else variant="muted" to="/auth">Sign in</AppButton>
        </div>
    </div>
</template>

<script setup lang="ts">
import AppButton from "@/components/ui/actions/AppButton.vue";

defineProps<{
    canAccessAdmin: boolean;
    canAccessAccount: boolean;
}>();
</script>
