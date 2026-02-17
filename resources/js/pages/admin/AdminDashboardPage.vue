<template>
    <section class="grid" style="gap: 1rem">
        <h1 style="margin: 0">Admin dashboard</h1>
        <div class="stack">
            <RouterLink class="btn btn-muted" to="/admin/products">Manage products</RouterLink>
            <RouterLink class="btn btn-muted" to="/admin/orders">Manage orders</RouterLink>
            <RouterLink class="btn btn-muted" to="/admin/promotions">Manage promotions</RouterLink>
        </div>
    </section>
</template>

<script setup lang="ts">
import { RouterLink } from 'vue-router';
</script>
