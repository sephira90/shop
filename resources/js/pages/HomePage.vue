<template>
    <section class="grid" style="gap: 1.2rem">
        <div class="card">
            <h1 style="margin-top: 0">Laravel + Vue Store</h1>
            <p style="margin-bottom: 0">Production-oriented ecommerce starter with catalog, cart, checkout and admin API.</p>
        </div>
        <div class="stack">
            <RouterLink class="btn btn-primary" to="/catalog">Browse catalog</RouterLink>
            <RouterLink class="btn btn-muted" to="/admin">Admin area</RouterLink>
        </div>
    </section>
</template>

<script setup lang="ts">
import { RouterLink } from 'vue-router';
</script>
