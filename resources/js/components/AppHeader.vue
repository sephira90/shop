<template>
    <header style="background: #fff; border-bottom: 1px solid #e2e8f0">
        <div class="container" style="display: flex; gap: 1rem; padding-block: 1rem; align-items: center">
            <RouterLink to="/" style="font-weight: 700; color: #0f172a">Shop</RouterLink>
            <RouterLink to="/catalog">Catalog</RouterLink>
            <RouterLink to="/cart">Cart</RouterLink>
            <RouterLink to="/account/orders">Account</RouterLink>
            <RouterLink to="/admin">Admin</RouterLink>
        </div>
    </header>
</template>

<script setup lang="ts">
import { RouterLink } from 'vue-router';
</script>
