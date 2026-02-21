<template>
    <section class="grid">
        <div class="hero">
            <span class="pill">Production-ready storefront</span>
            <h1>Laravel + Vue ecommerce shell</h1>
            <p>
                A clean baseline for catalog, checkout and admin operations with one visual language
                across all pages.
            </p>
            <div class="hero__actions">
                <RouterLink class="btn btn-primary" to="/catalog">Browse catalog</RouterLink>
                <RouterLink v-if="canAccessAdmin" class="btn btn-muted" to="/admin">Open admin area</RouterLink>
                <RouterLink v-else-if="canAccessAccount" class="btn btn-muted" to="/account/profile">Open account</RouterLink>
                <RouterLink v-else class="btn btn-muted" to="/auth">Sign in</RouterLink>
            </div>
        </div>

        <div class="grid grid-3">
            <article class="card kpi">
                <span class="kpi__value">Fast UI shell</span>
                <span class="kpi__label">Optimized SPA routing with clean refresh support.</span>
            </article>
            <article class="card kpi">
                <span class="kpi__value">Catalog first</span>
                <span class="kpi__label">Product discovery, variant selection and cart flow.</span>
            </article>
            <article class="card kpi">
                <span class="kpi__value">Admin ready</span>
                <span class="kpi__label">Dedicated pages for orders, products and promotions.</span>
            </article>
        </div>
    </section>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { RouterLink } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const authStore = useAuthStore();
const canAccessAdmin = computed<boolean>(() => authStore.canAccessAdmin);
const canAccessAccount = computed<boolean>(() => authStore.canAccessAccount);
</script>
